<?php
/**
 * Copyright 2018 AlexaCRM
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

namespace AlexaCRM\Nextgen\Forms\Recaptcha;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\Enum;

/**
 * Enumerates the types of reCAPTCHA.
 */
class Type extends Enum {

    /**
     * Visible reCAPTCHA input.
     */
    const V2_CHECKBOX = 'v2-checkbox';

    /**
     * Invisible reCAPTCHA with background validation.
     */
    const V2_INVISIBLE = 'v2-invisible';

    /**
     * Invisible reCAPTCHA with background validation and returning result as a score.
     */
    const V3 = 'v3';

}
