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

/**
 * Represents a notice for Wordpress Gutenberg editor.
 */
class GutenbergBlockNotice {
    /**
     * Success notice type.
     */
    const TYPE_SUCCESS = 'success';

    /**
     * Info notice type.
     */
    const TYPE_INFO = 'info';

    /**
     * Warning notice type.
     */
    const TYPE_WARNING = 'warning';

    /**
     * Error notice type.
     */
    const TYPE_ERROR = 'error';

    /**
     * Type of the notice
     *
     * @var string
     */
    public string $type = self::TYPE_INFO;

    /**
     * Notice massage.
     *
     * @var string
     */
    public string $message;

    /**
     * Determines whether the notice can be dismissed by user.
     *
     * @var bool
     */
    public bool $isDismissible = true;

    /**
     * GutenbergBlockNotice constructor.
     *
     * @param string $message
     */
    public function __construct( string $message ) {
        $this->message = $message;
    }

    /**
     * Appends a text line to the Notice message.
     *
     * @param string $string
     */
    public function addMessageLine( string $string ): void {
        $this->message .= PHP_EOL . $string;
    }
}
