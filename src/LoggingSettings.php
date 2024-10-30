<?php
/**
 * Copyright 2019 AlexaCRM
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

use Monolog\Logger;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Describes logging settings for the plugin.
 */
class LoggingSettings extends Settings {

    /**
     * Logging verbosity.
     *
     * @var int
     * @see \Monolog\Logger Contains verbosity constants.
     */
    public int $verbosity = Logger::WARNING;

    /**
     * Whether to send email notifications to administrator.
     *
     * @var bool
     */
    public bool $notificationsEnabled = true;

    public bool $sendNotifications = false;

    /**
     * Notifications verbosity.
     *
     * @var int
     * @see \Monolog\Logger Contains verbosity constants.
     */
    public int $notificationsVerbosity = Logger::ERROR;

    /**
     * Notifications frequency in hours.
     *
     * @var int
     */
    public int $notificationsFrequency = 24;

}
