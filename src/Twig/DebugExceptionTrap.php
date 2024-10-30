<?php
/**
 * Copyright 2020 AlexaCRM
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

namespace AlexaCRM\Nextgen\Twig;

use AlexaCRM\Nextgen\GutenbergBlockNotice;
use AlexaCRM\Nextgen\LoggerProvider;
use AlexaCRM\Nextgen\TwigProvider;

/**
 * A utility to trap any exception or error coming out of the Twig renderer.
 * Helps with adding extra debugging details to the output.
 * In production mode, just creates a generic error message.
 */
class DebugExceptionTrap {

    /**
     * Catches the exception or error, adds debug information to the output.
     * Also adds a Gutenberg notification for the WP editor.
     *
     * @param \Throwable $e
     *
     * @return string
     */
    public static function catchEx( \Throwable $e ): string {
        $hasXdebug = property_exists( $e, 'xdebug_message' );
        $hasInner = $e->getPrevious() instanceof \Throwable;

        $notice = new GutenbergBlockNotice( $e->getMessage() );
        $notice->type = GutenbergBlockNotice::TYPE_ERROR;

        $message = $tmplCode = '';

        if ( TwigProvider::isDebug() ) {
            $notice->addMessageLine( '<pre class="debug-info">' );

            if ( $hasXdebug ) {
                $notice->addMessageLine( '<table class="table table-sm text-break">' );

                $notice->addMessageLine( $hasInner ?
                    $e->getPrevious()->xdebug_message :
                    $e->xdebug_message
                );

                $notice->addMessageLine( '</table>' );
            } else {
                $notice->addMessageLine( $hasInner ?
                    htmlentities2( $e->getPrevious()->getTraceAsString() ) :
                    htmlentities2( $e->getTraceAsString() )
                );
            }

            $notice->addMessageLine( '</pre>' );

            add_filter( 'integration-cds/admin/gutenberg-notice', function( $notices ) use ( $notice ) {
                $notices[] = $notice;

                return $notices;
            } );

            $message = __( 'Failed to render the template. Please contact the site administrator.',
                'integration-cds' );

            $message .= ' ' . $notice->message;
            LoggerProvider::instance()->getLogger()->error( $message );
        } else {
            $notice = new GutenbergBlockNotice( $e->getMessage() );
            $notice->type = GutenbergBlockNotice::TYPE_ERROR;
            if ( method_exists( $e, 'getSourceContext' ) ) {
                if ( null !== $e->getSourceContext() && null !== $e->getSourceContext()->getCode() ) {
                    $tmplCode = $e->getSourceContext()->getCode() ?? '';
                }
                $notice->addMessageLine( $tmplCode );
            }

            LoggerProvider::instance()->getLogger()->error( $notice->message );
        }

        return $message;
    }

}
