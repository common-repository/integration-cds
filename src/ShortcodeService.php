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

use AlexaCRM\Nextgen\Shortcode\Twig;
use AlexaCRM\Nextgen\Twig\DebugExceptionTrap;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages shortcodes available through the plugin.
 */
class ShortcodeService {

    /**
     * A map of supported shortcodes and their implementations.
     */
    protected array $shortcodes = [
        'icds_twig' => Twig::class,
    ];

    /**
     * Stores shortcode implementations.
     *
     * @var ShortcodeInterface[]
     */
    protected array $shortcodeProcessors = [];

    /**
     * ShortcodeService constructor.
     */
    public function __construct() {
        /**
         * Filters the list of supported shortcodes and their respective implementations
         *
         * Every item key is the non-prefixed name of the shortcode, the value designates
         * a fully qualified class name that implements AlexaCRM\Nextgen\ShortcodeInterface
         * and implements a public method Shortcode::shortcode()
         *
         * @param array $shortcodes List of supported shortcodes
         */
        $this->shortcodes = apply_filters( 'integration-cds/shortcode/implementations', $this->shortcodes );

        foreach ( $this->shortcodes as $shortcodeName => $shortcodeClass ) {
            add_shortcode( $shortcodeName, [ $this, 'render' ] );
        }
    }

    /**
     * Renders the shortcodes.
     *
     * @param array|string $attributes
     * @param string $content
     * @param string $shortcodeName
     *
     * @return string
     */
    public function render( $attributes, string $content = '', string $shortcodeName = '' ): string {
        if ( !array_key_exists( $shortcodeName, $this->shortcodeProcessors ) ) {
            $shortcodeClassName = $this->shortcodes[ $shortcodeName ];
            $this->shortcodeProcessors[ $shortcodeName ] = new $shortcodeClassName();
        }

        $logger = LoggerProvider::instance()->getLogger();
        $logger->debug( __("Rendering the shortcode [{$shortcodeName}].", 'integration-cds') );

        try {
            $attrs = [];
            if ( is_array( $attributes ) ) {
                $attrs = $attributes; // WordPress provides an empty string if no attributes have been set.
            }
            $output = $this->shortcodeProcessors[ $shortcodeName ]->render( $attrs, $content );
        } catch ( \Exception $e ) {
            return DebugExceptionTrap::catchEx( $e );
        }

        return $output;
    }

}
