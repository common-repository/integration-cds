<?php
/*
Plugin Name:  Dataverse Integration
Plugin URI:   https://alexacrm.com/
Description:  Integrate Microsoft Dataverse organizations with WordPress.
Version:      2.67
Author:       AlexaCRM
Author URI:   https://alexacrm.com/
License:      MIT
License URI:  https://opensource.org/licenses/MIT
Text Domain:  integration-cds
Domain Path:  /languages
*/

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

/*
 * ATTENTION. Keep PHP code in this file as much backwards-compatible with previous PHP versions as possible.
 * It allows to fail gracefully and show user a proper warning.
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Entry point of the plugin.
 */
define( 'ICDS_FILE', __FILE__ );

/**
 * Basename of the plugin.
 */
define( 'ICDS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimal supported PHP version.
 */
define( 'ICDS_PHP_MIN_VERSION', '7.4' );

// Check the PHP version constraint.
require_once ABSPATH . 'wp-admin/includes/plugin.php';

if ( version_compare( PHP_VERSION, ICDS_PHP_MIN_VERSION, '<' ) ) {
    function icds_unmet_php_notice() {
        $screen = get_current_screen();
        if ( $screen === null || $screen->base !== 'plugins' ) {
            return;
        }

        ?>
        <div class="notice notice-error">
            <p>
                <?php printf( __( 'Dataverse Integration detected that your PHP version is %1$s. The plugin requires at least PHP %2$s to work. Please update your PHP installation to enable the plugin.', 'integration-cds' ), PHP_VERSION, ICDS_PHP_MIN_VERSION ); ?>
            </p>
        </div>
        <?php
        deactivate_plugins( ICDS_BASENAME );
    }

    add_action( 'admin_notices', 'icds_unmet_php_notice' );

    return;
}

/**
 * Plugin version.
 */
define( 'ICDS_VERSION', '2.67' );

/**
 * Public plugin URL.
 */
define( 'ICDS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Path to the plugin directory.
 */
define( 'ICDS_DIR', __DIR__ );

require_once 'core.php';

/**
 * Fires after Dataverse Integration has been initialized.
 */
do_action( 'integration-cds/initialized' );
