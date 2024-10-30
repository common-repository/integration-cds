<?php
/**
 * Copyright 2018-2019 AlexaCRM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
 * associated documentation files (the "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
 * OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace AlexaCRM\Nextgen;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\Twig\DebugExceptionTrap;
use AlexaCRM\Nextgen\Twig\IcdsExtension;
use AlexaCRM\Nextgen\Twig\MobileDetectExtension;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\DebugExtension;
use Twig\Extension\StringLoaderExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/**
 * Provides a universal Twig environment.
 */
class TwigProvider {

    use SingletonTrait;

    protected ?Environment $twigEnvironment = null;

    protected ?ArrayLoader $arrayLoader = null;

    /**
     * @var int[]
     */
    protected array $errors = [];

    /**
     * Tells whether Twig debug mode should be used.
     */
    public static function isDebug(): bool {
        $icdsTwigDebug = AdvancedSettingsProvider::instance( 'ICDS_TWIG_DEBUG' );
        return ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( $icdsTwigDebug->isTrue() );
    }

    /**
     * Renders the given template.
     *
     * @param string $tpl Twig template body.
     *
     * @return string
     */
    public function renderString( string $tpl ): string {
        $loader = $this->getArrayLoader();
        $twig = $this->getEnvironment();

        $templateKey = 'template_' . sha1( $tpl );
        $loader->setTemplate( $templateKey, $tpl );

        try {
            $content = $twig->render( $templateKey );
        } catch ( \Error|\Exception $e ) {
            return DebugExceptionTrap::catchEx( $e );
        }

        return $content;
    }

    /**
     * Provides access to the runtime array loader.
     */
    public function getArrayLoader(): ArrayLoader {
        if ( $this->arrayLoader instanceof ArrayLoader ) {
            return $this->arrayLoader;
        }

        $this->arrayLoader = new ArrayLoader();

        return $this->arrayLoader;
    }

    /**
     * Appends error information to the list of registered errors.
     *
     * @param int $code
     *
     * @return void
     */
    public function registerError( int $code ) {
        $this->errors[] = $code;
    }

    /**
     * Returns the last registered error.
     *
     * @return int
     */
    public function getLastError(): int {
        return end( $this->errors );
    }

    /**
     * Gives access to the Twig engine instance.
     */
    public function getEnvironment(): Environment {
        if ( $this->twigEnvironment instanceof Environment ) {
            return $this->twigEnvironment;
        }

        $chainLoader = new ChainLoader();

        $chainLoader->addLoader( $this->getArrayLoader() );

        /**
         * Allows extending the list of available Twig template loaders.
         *
         * @param ChainLoader $chainLoader
         */
        do_action( 'integration-cds/twig/add-loaders', $chainLoader );

        $templatePaths = [
            get_stylesheet_directory() . '/integration-cds/twig',
        ];

        if ( get_stylesheet_directory() !== get_template_directory() ) {
            $templatePaths[] = get_template_directory() . '/integration-cds/twig'; // Parent theme directory
        }

        $templatePaths[] = ICDS_DIR . '/templates/twig';

        /**
         * Filters the collection of Twig template paths.
         *
         * @param array $templatePaths
         */
        $templatePaths = apply_filters( 'integration-cds/twig/templates', $templatePaths );

        $fileLoader = new FilesystemLoader();

        foreach ( $templatePaths as $templatePath ) {
            try {
                $fileLoader->addPath( $templatePath );
            } catch ( LoaderError $e ) {
            }
        }
        $chainLoader->addLoader( $fileLoader );

        $isDebugEnabled = self::isDebug();
        $isCacheEnabled = AdvancedSettingsProvider::instance( 'ICDS_TWIG_CACHE' )->isTrue() && StorageHelper::isStorageAvailable();

        $twigEnv = new Environment( $chainLoader, [
            'debug' => $isDebugEnabled,
            'cache' => $isCacheEnabled ? StorageHelper::getStoragePath() . '/__twig/' : false,
        ] );

        $twigEnv->addExtension( new MobileDetectExtension() );
        $twigEnv->addExtension( new IntlExtension() );
        $twigEnv->addExtension( new StringLoaderExtension() );

        if ( $isDebugEnabled ) {
            $twigEnv->addExtension( new DebugExtension() );
        }

        // Add global variables to the context
        $this->addGlobals( $twigEnv );

        // Add filters to the environment
        $this->addFilters( $twigEnv );

        /**
         * Filters the collection of token parser implementations.
         *
         * @param array Array of FQCNs.
         *
         * @deprecated Use `integration-cds/twig/token-parsers-ext` instead.
         */
        $tokenParsers = apply_filters( 'integration-cds/twig/token-parsers', [] );

        foreach ( $tokenParsers as $parser ) {
            $twigEnv->addTokenParser( new $parser );
        }

        /*
         * Add the Dataverse Integration extension. Includes default implementations of Dataverse-related features,
         * as well as custom implementations delivered via corresponding WordPress filters.
         */
        $twigEnv->addExtension( new IcdsExtension() );

        /**
         * Fired when Twig environment has been set up in the shortcode.
         *
         * Allows to further extend the Twig environment with new features.
         *
         * @param Environment $twigEnv
         */
        do_action( 'integration-cds/twig/ready', $twigEnv );

        $this->twigEnvironment = $twigEnv;

        return $twigEnv;
    }

    /**
     * Adds global variables to the given environment object.
     *
     * @param Environment $twigEnv
     */
    private function addGlobals( Environment $twigEnv ): void {
        $isCdsAvailable = ConnectionService::instance()->isAvailable();

        if ( $isCdsAvailable ) {
            /**
             * Access to any entity
             *
             * {{ entities.logicalName["GUID"] }}
             */
            $twigEnv->addGlobal( 'entities', new Twig\FauxEntitiesCollection() );

            /**
             * List of entities as array
             *
             * [ 'entityLogicalName' => 'entityDisplayName' ]
             */
            $twigEnv->addGlobal( 'entities_list', MetadataService::instance()->getEntitiesList() );

            /**
             * Access to Dataverse metadata.
             *
             * {{ metadata["contact"].Attributes["gendercode"].EntityLogicalName }}
             */
            $twigEnv->addGlobal( 'metadata', new Twig\MetadataCollection() );
        }

        /**
         * Current time.
         */
        $twigEnv->addGlobal( 'now', time() );

        $request = Request::createFromGlobals();

        $params = array_merge( $request->cookies->all(), $request->request->all(), $request->query->all() );
        $twigEnv->addGlobal( 'params', $params );

        $twigEnv->addGlobal( 'request', [
            'params' => $params,
            'path' => $request->getPathInfo(),
            'path_and_query' => $request->getRequestUri(),
            'query' => $request->getQueryString()? '?' . $request->getQueryString() : '',
            'url' => $request->getUri(),
        ] );

        $twigEnv->addGlobal( 'crm', [
            'connected' => $isCdsAvailable,
        ] );

        /**
         * Triggered after default global variables are set up.
         *
         * @param Environment $twigEnv
         */
        do_action( 'integration-cds/twig/after-globals', $twigEnv );
    }

    /**
     * Adds default filters to the given environment object.
     *
     * Dataverse-related extensions are provided in IcdsExtension.
     *
     * @param Environment $twigEnv
     */
    private function addFilters( Environment $twigEnv ): void {
        $twigEnv->addFilter( new TwigFilter(
            'add_query',
            function( $url, $argName, $argValue ) {
                $req = Request::create( $url, 'GET', [ $argName => $argValue ] );

                return $req->getUri();
            }
        ) );

        $twigEnv->addFilter( new TwigFilter(
            'wpautop',
            function( $value ) {
                return wpautop( $value );
            }
        ) );

        $twigEnv->addFilter( new TwigFilter(
            'translate',
            function( $value ) {
                // Register string in wpml | Params: hook name, context name, string name, string value
                do_action( 'wpml_register_single_string', 'twig-translations', $value, $value );
                // Take translated value | Params: hook name, original value, context name, string value, (optional language_code)
                $value = apply_filters( 'wpml_translate_single_string', $value, 'twig-translations', $value );
                return $value;
            }
        ) );
    }

}
