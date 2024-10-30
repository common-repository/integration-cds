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

namespace AlexaCRM\Nextgen\Shortcode;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\GutenbergBlockNotice;
use AlexaCRM\Nextgen\ShortcodeInterface;
use AlexaCRM\Nextgen\Twig\DebugExceptionTrap;
use AlexaCRM\Nextgen\TwigProvider;
use Exception;

/**
 * Implements the Twig templates
 */
class Twig implements ShortcodeInterface {

    /**
     * Renders the shortcode.
     *
     * @param array $attributes
     * @param string $content
     *
     * @return string
     */
    public function render( array $attributes, string $content ): string {
        $provider = TwigProvider::instance();
        $loader = $provider->getArrayLoader();
        $twig = $provider->getEnvironment();

        $templateKey = 'template_' . sha1( $content );
        $loader->setTemplate( $templateKey, $content );

        try {
            $output = $twig->render( $templateKey );
        } catch ( \Exception $e ) {
            return DebugExceptionTrap::catchEx( $e );
        } catch ( \Error $e ){
            return DebugExceptionTrap::catchEx( $e );
        }

        // Process nested shortcodes
        return do_shortcode( $output );
    }

}
