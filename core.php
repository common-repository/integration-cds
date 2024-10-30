<?php
/**
 * Copyright 2018-2020 AlexaCRM
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

use AlexaCRM\Nextgen\Forms\Recaptcha\RecaptchaSettings;
use AlexaCRM\WebAPI\OData\AuthenticationException;
use AlexaCRM\Xrm\EntityReference;
use Monolog\Logger;

require_once 'vendor/autoload.php';

register_activation_hook( ICDS_FILE, function() {
    CacheProvider::instance()->clear();
} );

add_action( 'admin_init', function() {
    $storedVersion = get_option( 'icds_version' );
	if ( version_compare( $storedVersion, ICDS_VERSION, '==' ) ) {
        return;
	}

    if ( version_compare( $storedVersion, '2.26', '<' ) ) {
        UpdateManager::addExistingFetchXmlTemplates();
    }

    if ( version_compare( $storedVersion, '2.27', '<' ) ) {
        UpdateManager::updateCustomButtonLabels();
    }


    update_option( 'icds_version', ICDS_VERSION );
} );

// Init the script registry.
ScriptRegistry::instance();

/*
 * Add a menu item to the WordPress admin UI.
 */
add_action( 'admin_menu', static function() {
    $icdsIcon = ICDS_URL . 'front/admin/dataverse-icon-20.png';
    if ( defined( 'ICDS_NEW_ADMIN_URL' ) && ICDS_NEW_ADMIN_URL ) {
        $icdsIcon = ICDS_NEW_ADMIN_URL . '/dataverse-icon-20.png';
    }

    add_menu_page(
        __( 'Dataverse', 'integration-cds' ),
        __( 'Dataverse', 'integration-cds' ),
        'manage_options',
        'integration-cds',
        static function() {
        },
        $icdsIcon
    );
} );

add_action( 'load-toplevel_page_integration-cds', static function() {
    require __DIR__ . '/admin.php';
    exit();
} );

if ( defined( 'ICDS_SANDBOX' ) && ICDS_SANDBOX && file_exists( __DIR__ . '/sandbox.php' ) ) {
    add_action( 'admin_menu', static function() {
        add_menu_page(
            __( 'Dataverse Sandbox', 'integration-cds' ),
            __( 'Dataverse Sandbox', 'integration-cds' ),
            'manage_options',
            'icds-sandbox',
            static function() {
            }
        );
    } );

    add_action( 'load-toplevel_page_icds-sandbox', static function() {
        require __DIR__ . '/sandbox.php';
        exit();
    } );
}

add_filter( 'no_texturize_shortcodes', static function( $shortcodes ) {
    $shortcodes[] = 'icds_twig';

    return $shortcodes;
} );

/**
 * Register advanced settings.
 */
add_filter( 'integration-cds/settings/advanced', function( $settings ){
    $advSetting = new AdvancedSetting( 'ICDS_COMPATIBLE_BINDING' );
    $advSetting->type = AdvancedSetting::TYPE_BOOLEAN;
    $advSetting->default = false;
    $advSetting->description = 'If <b>ICDS_COMPATIBLE_BINDING</b> flag is set to true you can use the <b><i>currentrecord</i></b> variable that refers to the <b><i>binding.record</i></b>. This is intended mostly for backward compatibility with previous versions of the plugin and should not be used in general.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_DISABLE_MONACO' );
    $advSetting->type = AdvancedSetting::TYPE_BOOLEAN;
    $advSetting->default = false;
    $advSetting->description = 'If <b>ICDS_DISABLE_MONACO</b> is set to true Monaco editor will not be used at the admin pages for editing purposes. Regular textarea will be used instead.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_DISABLE_CACHE' );
    $advSetting->type = AdvancedSetting::TYPE_BOOLEAN;
    $advSetting->default = false;
    $advSetting->description = 'If <b>ICDS_DISABLE_CACHE</b> flag is set to true Dataverse Integration does not cache any Dataverse-related data.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_CACHE_STORAGE' );
    $advSetting->type = AdvancedSetting::TYPE_SELECT;
    $advSetting->default = CacheProvider::FORCE_AUTO;
    $advSetting->options = [
        CacheProvider::FORCE_AUTO => 'Auto',
        CacheProvider::FORCE_FILES => 'Files',
        CacheProvider::FORCE_WPCACHE => 'Object',
    ];
    $advSetting->description = 'By using the <b>ICDS_CACHE_STORAGE</b> constant you can force Dataverse Integration to use the specified storage.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_AUTH_KEY' );
    $advSetting->type = AdvancedSetting::TYPE_STRING;
    $advSetting->default = '';
    $advSetting->description = 'Using by Dataverse Integration for authentication purposes.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_FORM_AUTH_KEY' );
    $advSetting->type = AdvancedSetting::TYPE_STRING;
    $advSetting->default = '';
    $advSetting->description = 'Using by Dataverse Integration for authentication purposes.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_TWIG_DEBUG' );
    $advSetting->type = AdvancedSetting::TYPE_BOOLEAN;
    $advSetting->default = false;
    $advSetting->description = 'If <b>ICDS_TWIG_DEBUG</b> flag is set to true you can use <b><i>dump()</i></b> to print information about Twig objects using PHP <b><i>var_dump()</i></b>.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_TWIG_CACHE' );
    $advSetting->type = AdvancedSetting::TYPE_BOOLEAN;
    $advSetting->default = false;
    $advSetting->description = 'If <b>ICDS_TWIG_CACHE</b> flag is set to true template caching enhances page rendering performance.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_QM_LOGS' );
    $advSetting->type = AdvancedSetting::TYPE_BOOLEAN;
    $advSetting->default = false;
    $advSetting->description = 'If <b>ICDS_QM_LOGS</b> flag is set to true integration-cds logs will be duplicated in the query monitor.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_DB_LOGS' );
    $advSetting->type = AdvancedSetting::TYPE_BOOLEAN;
    $advSetting->default = false;
    $advSetting->description = 'If <b>ICDS_DB_LOGS</b> flag is set to true, logging uses <b>WordPress Transients API</b> feature instead of files. <br><b class="text-danger bg-light">IMPORTANT!</b> Enabling this option affects the site performance. Use for troubleshooting purposes and only if directed by support.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_DB_LOGS_EXPIRATION' );
    $advSetting->type = AdvancedSetting::TYPE_STRING;
    $advSetting->default = DatabaseLogHandler::STORE_IN_DB_EXPIRATION;
    $advSetting->description = 'Sets the expiration time in seconds for <b>ICDS_DB_LOGS</b>. Default value is 600.';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_ENTITY_FILTER' );
    $advSetting->type = AdvancedSetting::TYPE_SELECT;
    $advSetting->default = EntityFilter::ENTITY_ALL_FILTER;
    $advSetting->options = [
        EntityFilter::ENTITY_ALL_FILTER => EntityFilter::ENTITY_ALL_FILTER,
        EntityFilter::ENTITY_DEFAULT_FILTER => EntityFilter::ENTITY_DEFAULT_FILTER,
        EntityFilter::ENTITY_CUSTOM_FILTER => EntityFilter::ENTITY_CUSTOM_FILTER,
        EntityFilter::ENTITY_MANAGED_FILTER => EntityFilter::ENTITY_MANAGED_FILTER,
    ];
    $advSetting->description = 'Sets the filter type for the entity list';
    $settings[ $advSetting->key ] = $advSetting;

    $advSetting = new AdvancedSetting( 'ICDS_SDK_VERSION' );
    $advSetting->type = AdvancedSetting::TYPE_STRING;
    $advSetting->default = '9.1';
    $advSetting->description = 'Specify the SDK client version to use with connection to the Dataverse.';
    $settings[ $advSetting->key ] = $advSetting;

    // ICDS_LOG_LEVEL is not defined here because it already presents in the 'Status' panel.

    return $settings;
} );

/**
 * Register available settings by type
 */
add_filter( 'integration-cds/settings/map', static function( $settingsMap ) {
    $settingsType = new SettingsType( 'connection' );
    $settingsType->storageKey = 'icds_connection';
    $settingsType->className = ConnectionSettings::class;
    $settingsMap['connection'] = $settingsType;

    $settingsType = new SettingsType( 'logging' );
    $settingsType->storageKey = 'icds_logging';
    $settingsType->className = LoggingSettings::class;
    $settingsMap['logging'] = $settingsType;

    $settingsType = new SettingsType( 'recaptcha' );
    $settingsType->storageKey = 'icds_recaptcha';
    $settingsType->className = RecaptchaSettings::class;
    $settingsMap['recaptcha'] = $settingsType;

    $settingsType = new SettingsType( AdvancedSettings::SETTINGS_TYPE_NAME );
    $settingsType->storageKey = AdvancedSettings::STORAGE_KEY;
    $settingsType->className = AdvancedSettings::class;
    $settingsMap[ AdvancedSettings::SETTINGS_TYPE_NAME ] = $settingsType;

    $settingsType = new SettingsType( CacheSettings::SETTINGS_TYPE_NAME );
    $settingsType->storageKey = CacheSettings::STORAGE_KEY;
    $settingsType->className = CacheSettings::class;
    $settingsMap[ CacheSettings::SETTINGS_TYPE_NAME ] = $settingsType;

    return $settingsMap;
} );

/**
 * Register WP REST API endpoints.
 */
add_action( 'rest_api_init', static function() {
    $apiRegistrar = new API\Registrar();
    $apiRegistrar->registerEndpoints();
} );

/**
 * Register a new script.
 *
 * Registers a script to be enqueued later using the wp_enqueue_script() function.
 *
 * @param string $handle Name of the script. Should be unique.
 * @param string|bool $src Full URL of the script, or path of the script relative to the WordPress root directory.
 *                                    If source is set to false, script is an alias of other scripts it depends on.
 * @param array $deps Optional. An array of registered script handles this script depends on. Default empty array.
 * @param string|bool|null $ver Optional. String specifying script version number, if it has one, which is added to the URL
 *                                    as a query string for cache busting purposes. If version is set to false, a version
 *                                    number is automatically added equal to current installed WordPress version.
 *                                    If set to null, no version is added.
 * @param bool $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 *                                    Default 'false'.
 *
 * @return bool Whether the script has been registered. True on success, false on failure.
 * @see WP_Dependencies::add()
 * @see WP_Dependencies::add_data()
 *
 */
function wp_register_script( string $handle, $src, $deps = [], $ver = false, $in_footer = false ): bool {
    \wp_register_script( $handle, $src, $deps, $ver, $in_footer );

    $registered = ScriptRegistry::instance()->add( $handle, $src, $deps, $ver );
    if ( $in_footer ) {
        ScriptRegistry::instance()->add_data( $handle, 'group', 1 );
    }

    return $registered;
}

/**
 * Enqueue a script.
 *
 * Registers the script if $src provided (does NOT overwrite), and enqueues it.
 *
 * @param string $handle Name of the script. Should be unique.
 * @param string $src Full URL of the script, or path of the script relative to the WordPress root directory.
 *                                    Default empty.
 * @param array $deps Optional. An array of registered script handles this script depends on. Default empty array.
 * @param string|bool|null $ver Optional. String specifying script version number, if it has one, which is added to the URL
 *                                    as a query string for cache busting purposes. If version is set to false, a version
 *                                    number is automatically added equal to current installed WordPress version.
 *                                    If set to null, no version is added.
 * @param bool $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 *                                    Default 'false'.
 *
 * @see WP_Dependencies::add_data()
 * @see WP_Dependencies::enqueue()
 *
 * @since 2.1.0
 *
 * @see WP_Dependencies::add()
 */
function wp_enqueue_script( string $handle, $src = '', $deps = [], $ver = false, $in_footer = false ) {
    \wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );

    if ( $src || $in_footer ) {
        $_handle = explode( '?', $handle );

        if ( $src ) {
            ScriptRegistry::instance()->add( $_handle[0], $src, $deps, $ver );
        }

        if ( $in_footer ) {
            ScriptRegistry::instance()->add_data( $_handle[0], 'group', 1 );
        }
    }

    ScriptRegistry::instance()->enqueue( $handle );
}

/**
 * Localize a script.
 *
 * Works only if the script has already been added.
 *
 * Accepts an associative array $l10n and creates a JavaScript object:
 *
 *     "$object_name" = {
 *         key: value,
 *         key: value,
 *         ...
 *     }
 *
 * @param string $handle Script handle the data will be attached to.
 * @param string $object_name Name for the JavaScript object. Passed directly, so it should be qualified JS variable.
 *                            Example: '/[a-zA-Z0-9_]+/'.
 * @param mixed $l10n The data itself. The data can be either a single or multi-dimensional array.
 *
 * @return bool True if the script was successfully localized, false otherwise.
 */
function wp_localize_script( string $handle, string $object_name, $l10n ): bool {
    \wp_localize_script( $handle, $object_name, $l10n );

    return ScriptRegistry::instance()->localize( $handle, $object_name, $l10n );
}

/**
 * Registers resources for Admin UI.
 */
function registerAdminScripts(){
    static $touched = false;

    // Run once.
    if ( $touched ) {
        return;
    }

    $touched = true;

    $scripts = [
        'icds/gutenberg-notice' => [
            'src' => ICDS_URL . 'assets/js/gutenberg-notice.js',
            'dependencies' => [ 'wp-notices', 'lodash' ],
            'version' => ICDS_VERSION,
            'inFooter' => true,
        ],

        'icds/gutenberg-icon' => [
            'src' => ICDS_URL . 'assets/js/icds-icon.js',
            'dependencies' => [ 'wp-element' ],
            'version' => ICDS_VERSION,
            'inFooter' => true,
        ],
    ];

    if ( !AdvancedSettingsProvider::instance( 'ICDS_DISABLE_MONACO' )->isTrue() ) {
        // We include some third party plugin scripts here as dependencies to resolve script loading conflicts.
        $thirdPartyDeps = [];
        if ( is_plugin_active( 'elementor/elementor.php' ) ) {
            $thirdPartyDeps[] = 'elementor-common';
        }

        $scripts = array_merge( $scripts, [
            'icds/vendor/monaco-loader' => [
                'src' => ICDS_URL . 'assets/vendor/monaco-editor/loader.js',
                'dependencies' => array_merge( $thirdPartyDeps, [] ),
                'version' => '0.25.1',
                'inFooter' => true,
            ],
            'icds/vendor/monaco-nls' => [
                'src' => ICDS_URL . 'assets/vendor/monaco-editor/editor/editor.main.nls.js',
                'dependencies' => [],
                'version' => '0.25.1',
                'inFooter' => true,
            ],
            'icds/vendor/monaco-editor' => [
                'src' => ICDS_URL . 'assets/vendor/monaco-editor/editor/editor.main.js',
                'dependencies' => [ 'icds/vendor/monaco-loader', 'icds/vendor/monaco-nls' ],
                'version' => '0.25.1',
                'inFooter' => true,
            ],
            'icds/monaco-editor-twig' => [
                'src' => ICDS_URL . 'assets/js/monaco-twig.js',
                'dependencies' => [ 'icds/vendor/monaco-editor' ],
                'version' => ICDS_VERSION,
                'inFooter' => true,
            ],
            'icds/react-monaco' => [
                'src' => ICDS_URL . 'assets/js/react-monaco.js',
                'dependencies' => [ 'icds/monaco-editor-twig', 'wp-blocks', 'wp-element' ],
                'version' => ICDS_VERSION,
                'inFooter' => true,
            ],
            'icds/gutenberg-monaco-block' => [
                'src' => ICDS_URL . 'assets/js/gutenberg-monaco-block.js',
                'dependencies' => [
                    'wp-element',
                    'wp-blocks',
                    'wp-i18n',
                    'icds/react-monaco',
                    'icds/gutenberg-icon',
                ],
                'version' => ICDS_VERSION,
                'inFooter' => true,
            ],
        ] );
    }else{
        $scripts = array_merge($scripts, [
            'icds/gutenberg-monaco-block' => [
                'src' => ICDS_URL . 'assets/js/gutenberg-text-block.js',
                'dependencies' => [
                    'wp-element',
                    'wp-blocks',
                    'wp-i18n',
                    'icds/gutenberg-icon',
                ],
                'version' => ICDS_VERSION,
                'inFooter' => true,
            ],
        ]);
    }

    /**
     * Filters the list of Javascript files to be registered in admin area.
     *
     * @param array $scripts Associative array of files.
     */
    $scripts = apply_filters( 'integration-cds/admin/scripts', $scripts );

    // If user in theme customizer, e.g. url = /wp-admin/customize.php*
    if ( is_customize_preview() ) {
        $scripts = [];
    }

    foreach ( $scripts as $handle => $def ) {
        LoggerProvider::instance()->getLogger()->debug( 'Register administrative script: ' . $handle, [ 'definition' => $def, ] );
        wp_register_script( $handle, $def['src'], $def['dependencies'], $def['version'], $def['inFooter'] );
    }
}

/**
 * Registers resources for frontend.
 */
function registerPublicScripts() {
    static $touched = false;

    // Run once.
    if ( $touched ) {
        return;
    }

    $touched = true;

    /**
     * Filters the list of Javascript files to be registered in frontend pages.
     *
     * @param array $scripts Associative array of files.
     */
    $scripts = apply_filters( 'integration-cds/public/scripts', [
        'icds/custom-form' => [
            'src' => ICDS_URL . 'front/custom-form/custom-form.umd.min.js',
            'dependencies' => [],
            'version' => ICDS_VERSION,
            'inFooter' => true,
        ],
    ] );

    // Dev server version of the custom-form app.
    if ( defined( 'ICDS_CUSTOM_FORM_SERVE' ) && is_array( ICDS_CUSTOM_FORM_SERVE ) ) {
        $scripts['icds/custom-form'] = [
            'src' => ICDS_CUSTOM_FORM_SERVE[0],
            'dependencies' => [ 'icds/custom-form-vendor' ],
            'version' => ICDS_VERSION,
            'inFooter' => true,
        ];
        $scripts['icds/custom-form-vendor'] = [
            'src' => ICDS_CUSTOM_FORM_SERVE[1],
            'dependencies' => [],
            'version' => ICDS_VERSION,
            'inFooter' => true,
        ];
    }

    foreach ( $scripts as $handle => $def ) {
        wp_register_script( $handle, $def['src'], $def['dependencies'], $def['version'], $def['inFooter'] );
    }

    $scriptsLocalizes = apply_filters( 'integration-cds/public/scripts/localize', [] );

    $scriptsLocalizes['icds/custom-form'] = [
        'name' => 'icdsAPIDefaults',
        'value' => getApiDefaults(),
    ];

    foreach ( $scriptsLocalizes as $handle => $var ) {
        wp_localize_script( $handle, $var['name'], $var['value'] );
    }
}

/**
 * Register a CSS stylesheet.
 *
 * @param string $handle Name of the stylesheet. Should be unique.
 * @param string|bool $src Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
 *                                 If source is set to false, stylesheet is an alias of other stylesheets it depends on.
 * @param array $deps Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
 * @param string|bool|null $ver Optional. String specifying stylesheet version number, if it has one, which is added to the URL
 *                                 as a query string for cache busting purposes. If version is set to false, a version
 *                                 number is automatically added equal to current installed WordPress version.
 *                                 If set to null, no version is added.
 * @param string $media Optional. The media for which this stylesheet has been defined.
 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
 *
 * @return bool Whether the style has been registered. True on success, false on failure.
 */
function wp_register_style( string $handle, $src, $deps = [], $ver = false, $media = 'all' ): bool {
    \wp_register_style( $handle, $src, $deps, $ver, $media );

    return StyleRegistry::instance()->add( $handle, $src, $deps, $ver, $media );
}

/**
 * Enqueue a CSS stylesheet.
 *
 * Registers the style if source provided (does NOT overwrite) and enqueues.
 *
 * @param string $handle Name of the stylesheet. Should be unique.
 * @param string $src Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
 *                                 Default empty.
 * @param array $deps Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
 * @param string|bool|null $ver Optional. String specifying stylesheet version number, if it has one, which is added to the URL
 *                                 as a query string for cache busting purposes. If version is set to false, a version
 *                                 number is automatically added equal to current installed WordPress version.
 *                                 If set to null, no version is added.
 * @param string $media Optional. The media for which this stylesheet has been defined.
 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
 */
function wp_enqueue_style( string $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) {
    \wp_enqueue_style( $handle, $src, $deps, $ver, $media );

    if ( $src ) {
        $_handle = explode( '?', $handle );
        StyleRegistry::instance()->add( $_handle[0], $src, $deps, $ver, $media );
    }
    StyleRegistry::instance()->enqueue( $handle );
}

/**
 * Registers stylesheets for Admin UI.
 */
function registerAdminStyles() {
    $styles = [
        'icds/admin-dashboard' => [
            'src' => ICDS_URL . 'assets/css/dashboard.css',
            'dependencies' => [],
            'version' => ICDS_VERSION,
        ],
    ];

    if ( !AdvancedSettingsProvider::instance( 'ICDS_DISABLE_MONACO' )->isTrue() ) {
        $styles = array_merge( $styles, [
            'icds/vendor/monaco-editor' => [
                'src' => ICDS_URL . 'assets/vendor/monaco-editor/editor/editor.main.css',
                'dependencies' => [],
                'version' => '0.25.1',
            ],
        ] );
    }

    /**
     * Filters the list of CSS files to be registered in admin area.
     *
     * @param array $styles Associative array of files.
     */
    $styles = apply_filters( 'integration-cds/admin/styles', $styles );

    foreach ( $styles as $handle => $def ) {
        LoggerProvider::instance()->getLogger()->debug( 'Register administrative style: ' . $handle, [ 'definition' => $def, ] );
        wp_register_style( $handle, $def['src'], $def['dependencies'], $def['version'] );
    }
}

/**
 * Registers stylesheets for frontend.
 */
function registerPublicStyles() {
    /**
     * Filters the list of CSS files to be registered in frontend pages.
     *
     * @param array $styles Associative array of files.
     */
    $styles = apply_filters( 'integration-cds/public/styles', [
        'icds/public-forms' => [
            'src' => ICDS_URL . 'assets/css/forms.css',
            'dependencies' => [],
            'version' => ICDS_VERSION,
        ],
    ] );

    foreach ( $styles as $handle => $def ) {
        wp_register_style( $handle, $def['src'], $def['dependencies'], $def['version'] );
    }
}

/**
 * Register scripts and styles for Admin UI.
 */
add_action( 'admin_enqueue_scripts', 'AlexaCRM\Nextgen\registerAdminScripts' );
add_action( 'admin_enqueue_scripts', 'AlexaCRM\Nextgen\registerAdminStyles' );

/**
 * Enqueue scripts and styles for all admin dashboard.
 */
add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_style( 'icds/admin-dashboard' );
} );

/**
 * Enqueue scripts and styles for frontend.
 */
add_action( 'wp_enqueue_scripts', 'AlexaCRM\Nextgen\registerPublicScripts' );
add_action( 'wp_enqueue_scripts', 'AlexaCRM\Nextgen\registerPublicStyles' );

/**
 * Initialize Admin UI settings.
 */
add_filter( 'integration-cds/admin/settings', static function( $settingsMap ) {
    $settings = SettingsProvider::instance();

    $settingsMap['logging'] = $settings->getSettings( 'logging' );

    /** @var ConnectionSettings $connectionSettings */
    $connectionSettings = $settings->getSettings( 'connection' );
    $authenticationFields = [ 'applicationID', 'applicationSecret' ];

    if ( $connectionSettings->authenticationSettings !== null ) {
        $connectionSettings->authenticationSettings->hideSecretFields();

        foreach ( $authenticationFields as $field ) {
            if ( !isset( $connectionSettings->authenticationSettings->{$field} ) ) {
                $connectionSettings->authenticationSettings->{$field} = null;
            }
        }
    } else {
        $connectionSettings->authenticationSettings = new OnlineS2SSecretAuthenticationSettings();
    }

    $settingsMap['connection'] = $connectionSettings;
    $settingsMap['recaptcha'] = $settings->getSettings( 'recaptcha' );
    $settingsMap[ AdvancedSettings::SETTINGS_TYPE_NAME ] = $settings->getSettings( AdvancedSettings::SETTINGS_TYPE_NAME );
    $settingsMap[ CacheSettings::SETTINGS_TYPE_NAME ] = $settings->getSettings( CacheSettings::SETTINGS_TYPE_NAME );

    return $settingsMap;
} );

/**
 * Returns default settings for api.js.
 *
 * @return array
 */
function getApiDefaults(): array {
    /**
     * Filters the data required for api.js.
     *
     * @param array $defaults
     */
    return apply_filters( 'integration-cds/js/api-defaults', [
        'contentBaseUrl' => ICDS_URL,
        'endpointBaseUrl' => get_rest_url(),

        'nonce' => wp_create_nonce( 'wp_rest' ),
    ] );
}

/**
 * Purge caches if connection settings have been changed, warm-up connection and metadata.
 */
add_action( 'integration-cds/settings/updated', function( Settings $settings, $hasChanged ) {
    if ( !$hasChanged || !( $settings instanceof ConnectionSettings ) ) {
        return;
    }

    CacheProvider::instance()->clear();

    wp_schedule_single_event( time(), 'integration-cds/cache/warmup', [
        uniqid( '', true ), // Force WP scheduler to enqueue the job.
    ] );
    spawn_cron();
}, 10, 2 );

/**
 * Warms up connection and metadata caches.
 */
add_action( 'integration-cds/cache/warmup', function() {
    // Nothing to warm up if no connection available.
    if ( !ConnectionService::instance()->isAvailable() ) {
        return;
    }

    /*
     * Warm-up connection cache.
     *
     * This call will cache OData metadata.
     */
    ConnectionService::instance()->getClient()->getClient()->getMetadata();

    $service = MetadataService::instance();

    /**
     * Filters the list of entities which must be pre-cached.
     *
     * @param array $entities
     */
    $entities = apply_filters( 'integration-cds/cache/warmup-entities', [] );
    $registry = $service->getRegistry();
    foreach ( $entities as $entity ) {
        try {
            $registry->getDefinition( $entity );
        } catch ( AuthenticationException $e ) {
            LoggerProvider::instance()->getLogger()->error(
                __( 'Caught an AuthenticationException during cache warm-up. Warm-up interrupted.', 'integration-cds' ), [ 'exception' => $e ]
            );
            break;
        } catch ( \AlexaCRM\WebAPI\Exception $e ) {
            continue;
        }
    }
} );

/**
 * Add data to Admin UI.
 */
add_filter( 'integration-cds/admin/js-variables', function( $globalVariables ) {
    $globalVariables['icdsVersion'] = ICDS_VERSION;

    $globalVariables['icdsTranslates'] = include 'languages/front.php';

    $globalVariables['icdsSystemInformation'] = [
        'system' => InformationProvider::instance()->getSystemInformation(),
        'plugin' => InformationProvider::instance()->getPluginInformation(),
        'resources' => InformationProvider::instance()->getResourcesInformation(),
    ];

    if ( CheckRequirementsService::instance()->hasRecommended() ) {
        $globalVariables['icdsSystemInformation']['recommendations'] = CheckRequirementsService::instance()->getErrors()[ CheckRequirementsService::RECOMMENDED ];
    }

    $globalVariables['icdsRecentLogRecords'] = LoggerProvider::instance()->getRecentLogRecords( 10 );

    $globalVariables['icdsAddons'] = AddonsManager::instance()->getActiveAddons();
    $globalVariables['icdsAvailableAddons'] = array_diff_key(
        AddonsManager::instance()->getManagedAddons(),
        AddonsManager::instance()->getActiveAddons()
    );

    if ( InformationProvider::instance()->isSolutionInstalled() ) {
        if ( isset( $globalVariables['icdsAvailableAddons']['solution'] ) ) {
            $globalVariables['icdsAddons']['solution'] = $globalVariables['icdsAvailableAddons']['solution'];
            unset( $globalVariables['icdsAvailableAddons']['solution'] );
        }
    }

    $globalVariables['icdsLoggingVerbosity'] = [
        'Debug' => Logger::DEBUG,
        'Info' => Logger::INFO,
        'Notice' => Logger::NOTICE,
        'Warning' => Logger::WARNING,
        'Error' => Logger::ERROR,
        'Critical' => Logger::CRITICAL,
        'Alert' => Logger::ALERT,
        'Emergency' => Logger::EMERGENCY,
    ];

    $globalVariables['icdsLoggingSettingsDefaults'] = [
        'verbosity' => Logger::WARNING,
        'notificationsEnabled' => true,
        'notificationsVerbosity' => Logger::ERROR,
        'notificationsFrequency' => 24,
    ];

    $globalVariables['icdsCache'] = [
        'adapter' => CacheProvider::instance()->getCacheAdapter(),
        'pools' => [
            'metadata' => 'Metadata',
            'fetchxml' => 'FetchXML',
            'forms' => 'Forms',
            'views' => 'Views',
            'entityrecords' => 'Table Records',
            '__misc' => 'Misc',
        ],
    ];

    $globalVariables['icdsAdvancedSettings'] = array_map( function( $setting ) {
        $result = (array)$setting;

        if ( AdvancedSettingsProvider::instance( $setting->key )->isSet() ) {
            $result['value'] = AdvancedSettingsProvider::instance( $setting->key )->getValue();
            $result['source'] = AdvancedSettingsProvider::instance( $setting->key )->getSource();
        }

        return $result;
    }, AdvancedSettingsProvider::$registeredSettings );

    return $globalVariables;
} );

add_action( 'enqueue_block_editor_assets', function() {
    registerAdminScripts();
    registerAdminStyles();

    /**
     * Filters the list of notice which should be shown at Gutenberg editor page.
     *
     * @param GutenbergBlockNotice[] $notices
     */
    $notices = apply_filters( 'integration-cds/admin/gutenberg-notice', [] );
    wp_localize_script( 'icds/gutenberg-notice', 'icdsGutenbergBlockNotices', $notices );

    wp_enqueue_style(
        'icds/gutenberg-css',
        ICDS_URL . 'assets/css/gutenberg.css',
        [],
        ICDS_VERSION
    );
    wp_enqueue_script( 'icds/gutenberg-notice' );

    /*
     * Register Monaco block.
     */
    /**
     * Add `data-name` attribute to the Monaco stylesheet <link/>.
     *
     * When Monaco is loaded, it checks if the stylesheet has been loaded. It does so by comparing <link/> `src` or `data-name`
     * attributes. If any of them gives a match, then Monaco assumes that the stylesheet has been loaded. We use
     * version query tags (e.g. ?0.25.1) for cache busting, but that interferes with Monaco - it checks for equality
     * instead of `startsWith`.
     *
     * In this procedure, we add `data-name` with stylesheet identifier `vs/editor/editor.main`. It ensures that Monaco
     * locates the stylesheet and loads correctly.
     */
    add_filter( 'style_loader_tag', function( $tag, $handle, $href, $media ) {
        if ( $handle !== 'icds/vendor/monaco-editor' ) {
            return $tag;
        }

        LoggerProvider::instance()->getLogger()->debug( 'Register special \'data-name\' attribute for Monaco editor style tag.', [ $tag, $handle, $href, $media, ] );

        return sprintf( '<link rel="stylesheet" data-name="vs/editor/editor.main" id="%1$s-css"  href="%2$s" media="%3$s">', $handle, $href, $media );
    }, 10, 4 );

    LoggerProvider::instance()->getLogger()->debug( 'Localize \'monaco-loader\' script for Monaco editor.' );
    wp_localize_script( 'icds/vendor/monaco-loader', 'require', [
        'paths' => [
            'vs' => ICDS_URL . 'assets/vendor/monaco-editor',
        ],
    ] );

    LoggerProvider::instance()->getLogger()->debug( 'Register Monaco editor block for Gutenberg.');
    register_block_type( 'icds/gutenberg-monaco-block', [
        'editor_script' => 'icds/gutenberg-monaco-block',
        'editor_style' => 'icds/vendor/monaco-editor',
    ] );
}, 9 );

/**
 * Add the renderer for the Monaco block.
 *
 * Renders the Twig code contained in the Gutenberg block.
 */
add_filter( 'render_block', function( $content, $block ) {
    if ( !isset( $block['blockName'] ) || $block['blockName'] !== 'icds/gutenberg-monaco-block' ) {
        return $content;
    }

    LoggerProvider::instance()->getLogger()->debug( 'Render Twig template for Monaco block in Gutenberg.' );

    return TwigProvider::instance()->renderString( $content );
}, 10, 2 );

/**
 * Initializes addons, public services.
 */
add_action( 'integration-cds/initialized', function() {
    new ShortcodeService();
} );

add_action( 'admin_notices', function() {
    $screen = get_current_screen();
    if ( $screen === null || $screen->base !== 'plugins' ) {
        return;
    }

    if ( !ConnectionService::instance()->isAvailable() ) {
        return;
    }

    if ( !InformationProvider::instance()->isSolutionInstalled() ) {
        ?>
      <div class="notice notice-warning">
        <p>
            <?php printf(
                __( 'Thank you for using the free version of Dataverse Integration. Try our <a href="%s" class="alert-link" target="_blank">Power Platform solution</a> to unlock more exciting features.', 'integration-cds' ),
                InformationProvider::SOLUTION_MARKETPLACE_URL
            ); ?>
        </p>
      </div>
        <?php
        return;
    }

    if ( !InformationProvider::instance()->isPremiumInstalled() ) {
        $nonce = wp_create_nonce( 'install-plugin_integration-cds-premium' );
        ?>
      <div class="notice notice-warning">
        <p>
            <?php printf(
                __( 'Thank you for using the free version of Dataverse Integration. <a href="%s" class="alert-link">Download & install</a> the premium add-on to unlock more exciting features.', 'integration-cds' ),
                admin_url( 'update.php?action=install-plugin&plugin=integration-cds-premium&_wpnonce=' . $nonce )
            ); ?>
        </p>
      </div>
        <?php
    }

    if ( InformationProvider::instance()->isPremiumActive() && InformationProvider::instance()->getPremiumErrors() ) {
        ?>
        <div class="notice notice-error">
            <p>
                Dataverse Integration.
                <?php foreach ( InformationProvider::instance()->getPremiumErrors() as $error ) {
                    echo $error->get_error_message();
                } ?>
            </p>
        </div>
        <?php
    }
} );

/**
 * Return premium addon update information for UI.
 */
add_filter( 'plugins_api', function( $result, $action, $args ) {
    if ( defined( 'ICDSP_VERSION' ) ) {
        return $result; // Yield to the premium addon to manage its updates.
    }

    if ( $action !== 'plugin_information' || $args->slug !== 'integration-cds-premium' ) {
        return $result;
    }

    $client = ConnectionService::instance()->getClient();
    if ( $client === null ) {
        return new \WP_Error( 'plugins_api_failed', 'Please configure the connection to your Dataverse organization before you install the premium add-on.' );
    }

    $odClient = $client->getClient();
    try {
        $resp = $odClient->executeAction( 'alexacrm_WordPressRequest', [
            'Version' => '1.0.0.0',
            'Caller' => 'Dataverse Integration/' . ICDS_VERSION,
            'Site' => site_url(),
            'Name' => 'GetAddonUpdate',
            'Request' => json_encode( [
                'identifier' => 'integration-cds-premium',
            ] ),
        ] );
    } catch ( \Exception $e ) {
        $resp = null;
    }

    if ( $resp === null ) {
        return new \WP_Error( 'plugins_api_failed', __( 'Failed to retrieve the installation package for Dataverse Integration Premium plugin. Verify that the Dataverse solution is installed and your WordPress environment can reach your Dataverse organization.<br>If the issue persists, please visit the Addon tab for manual download links.', 'integration-cds' ) );
    }

    $meta = json_decode( $resp->Response );

    return (object)[
        'name' => 'Dataverse Integration Premium',
        'slug' => 'integration-cds-premium',
        'version' => $meta->version,
        'sections' => [
            'description' => $meta->description,
            'changelog' => $meta->changelog,
        ],
        'download_link' => $meta->packageUrl,
    ];
}, 10, 3 );

/**
 * Attempts to activate premium plugin after base plugin activation if it's installed.
 */
add_action( 'activated_plugin', function( $plugin ) {
    if ( $plugin !== ICDS_BASENAME ) {
        return;
    }

    $premiumInstalled = InformationProvider::instance()->isPremiumInstalled();
    $premiumActive = InformationProvider::instance()->isPremiumActive();

    if ( $premiumInstalled && !$premiumActive ) {
        activate_plugin( InformationProvider::PREMIUM_ID );
    }
} );

add_action( 'integration-cds/initialized', function() {
    if ( CheckRequirementsService::instance()->hasMandatory() ) {
        add_action( 'admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p>
                    <?php printf( __( 'Dataverse Integration detected that reqiured PHP extensions are missing: <b>%s</b>. Please update your PHP installation to enable the plugin.', 'integration-cds' ), implode( ',', CheckRequirementsService::instance()->getMissingMandatory() ) ); ?>
                </p>
            </div>
            <?php
            deactivate_plugins( ICDS_BASENAME );
        } );
    }
} );

/**
 * Attempts to deactivate premium plugin if base plugin deactivation is called and premium is installed.
 */
add_action( 'load-plugins.php', function() {
    if ( !isset( $_GET['action'] ) || $_GET['action'] !== 'deactivate' || $_GET['plugin'] !== ICDS_BASENAME ) {
        return;
    }

    $premiumActive = InformationProvider::instance()->isPremiumActive();

    if ( $premiumActive ) {
        deactivate_plugins( [ ICDS_BASENAME, InformationProvider::PREMIUM_ID ], true );
    }
} );

// Register the webhook post type.
add_action( 'init', function() {
    register_post_type( 'icds_webhook', [
        'public' => false,
    ] );
} );

/*
 * Register cron job to send errors notifications to administrators.
 */
add_filter( 'cron_schedules', function( $schedules ) {
    /** @var LoggingSettings $loggingSettings */
    $loggingSettings = SettingsProvider::instance()->getSettings( 'logging' );

    if ( !$loggingSettings->notificationsEnabled ) {
        return $schedules;
    }

    $schedules[ 'icds_schedule_' . $loggingSettings->notificationsFrequency ] = [
        'interval' => $loggingSettings->notificationsFrequency * HOUR_IN_SECONDS,
        'display' => __( "Every {$loggingSettings->notificationsFrequency} hours", 'integration_cds' ),
    ];

    return $schedules;
} );

add_action( 'init', function() {
    /** @var LoggingSettings $loggingSettings */
    $loggingSettings = SettingsProvider::instance()->getSettings( 'logging' );

    $eventHook = 'integration-cds/schedule/report-errors';
    $eventSchedule = 'icds_schedule_' . $loggingSettings->notificationsFrequency;
    $event = wp_get_scheduled_event( $eventHook );

    if ( $event === false ) {
        wp_schedule_event( time(), $eventSchedule, $eventHook );
    } elseif ( $event->schedule !== $eventSchedule ) {
        wp_unschedule_hook( $eventHook );
        wp_schedule_event( time(), $eventSchedule, $eventHook );
    }
} );

/*
 * Get errors and send notification
 */
add_action( 'integration-cds/schedule/report-errors', function() {
    /** @var LoggingSettings $loggingSettings */
    $loggingSettings = SettingsProvider::instance()->getSettings( 'logging' );

    $filters = [
        LogFilter::LEVEL_TYPE => 'ERROR',
        LogFilter::DATETIME_TYPE => [
            LogFilter::DATETIME_TYPE_BETWEEN,
            time() - $loggingSettings->notificationsFrequency,
            time(),
        ],
    ];

    $errors = LoggerProvider::instance()->getRecentLogRecords( 500, $filters );
    $adminEmail = get_option( 'admin_email' );

    if ( empty( $errors ) || empty( $adminEmail ) ) {
        return;
    }

    // Transform errors array
    $errors = array_map( function( $error ) {
        return "[{$error->datetime}] {$error->message} . Context: {$error->context}";
    }, $errors );

    $siteUrl = get_bloginfo( 'url' );

    $message = implode( '<br/>', $errors );
    $message = "Logs summary for the website {$siteUrl}: <br/>{$message}";
    $headers[] = "Content-type: text/plain; charset=utf-8";
    $headers[] = "From:noreply@{$siteUrl}";

    if ( wp_mail( $adminEmail, "Logs summary for website: {$siteUrl}", $message, $headers, '' ) ) {
        $logger = LoggerProvider::instance()->getLogger();
        $logger->info( 'Error notification successfully sent to ' . $adminEmail );
    }
} );

