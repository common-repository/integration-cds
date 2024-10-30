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

use foroco\BrowserDetection;
use Symfony\Component\HttpFoundation\Request;

/**
 * User browser helper class
 *
 * Class DetectBrowserUtil
 *
 * @package AlexaCRM\Nextgen
 */
class DetectBrowserUtil {

    /**
     * Returns user browser info
     *
     * @return array|mixed
     */
    public static function getBrowser() {
        $browser = new BrowserDetection();
        $useragent = Request::createFromGlobals()->headers->get( 'User-Agent' );

        return $browser->getBrowser( $useragent );
    }

    /**
     * Returns whether user browser is supported or not
     *
     * @return bool
     */
    public static function isBrowserSupported(): bool {
        $browserInfo = self::getBrowser();
        if ( !empty( $browserInfo ) ) {
            if (
                $browserInfo['browser_name'] === 'Internet Explorer' ||
                $browserInfo['browser_name'] === 'Opera Mini' ||
                ( $browserInfo['browser_name'] === 'Edge' && $browserInfo['browser_version'] < 79.0 )
            ) {
                LoggerProvider::instance()->getLogger()->warning( 'Unsupported browser detected', [
                    'browser' => $browserInfo,
                    'page' => Request::createFromGlobals()->getUri(),
                ] );

                return false;
            }
        }

        return true;
    }

    /**
     * Returns html with notice about unsupported browser and list of supported browsers
     *
     * @return string
     */
    public static function getBrowserNotSupportedHtml(): string {
        return 'Unable to render the form: unsupported browser';
    }
}
