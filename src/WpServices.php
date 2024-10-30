<?php
/**
 * Copyright 2022 AlexaCRM
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

/**
 * Provides easy access to the various WordPress services and utilites.
 */
class WpServices {

    /**
     * Represents an error message type.
     */
    const NOTICE_ERROR = 'error';

    /**
     * Represents an info message type.
     */
    const NOTICE_INFO = 'info';

    /**
     * Represents a success message type.
     */
    const NOTICE_SUCCESS = 'success';

    /**
     * Represents a warning message type.
     */
    const NOTICE_WARNNG = 'warning';

    /**
     * Adds a message to the message queue in the admin area.
     *
     * @param string $message Message text.
     * @param string $type Message type.
     * @param bool $dismissible Whether the message should be dismissable.
     *
     * @return void
     */
    public static function showAdminNotice( string $message, string $type = WpServices::NOTICE_INFO, bool $dismissible = false ) {
        $dismissibleClass = $dismissible ? ' is-dismissible' : '';

        add_action( 'admin_notices', function() use ( $message, $type, $dismissibleClass ) {
            ?>
            <div class="notice notice-<?php echo $type; ?><?php echo $dismissibleClass; ?>">
                <p><?php echo $message; ?></p>
            </div>
            <?php
        } );
    }

}
