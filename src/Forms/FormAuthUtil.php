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

namespace AlexaCRM\Nextgen\Forms;

use AlexaCRM\Nextgen\AdvancedSettingsProvider;
use AlexaCRM\Nextgen\LoggerProvider;

/**
 * Provides form submission authentication utilities.
 *
 * Form submission endpoints accept authentication reference codes to prevent forged submission metadata.
 */
class FormAuthUtil {

    /**
     * Hash algorithm used for generating codes.
     */
    const HMAC_ALGO = 'sha512';

    /**
     * Generates a base64-encoded string that authenticates the given form reference string.
     *
     * @param string $refString
     *
     * @return string
     */
    public static function generateRefCode( string $refString ): string {
        return base64_encode( hash_hmac( self::HMAC_ALGO, $refString, self::getKey(), true ) );
    }

    /**
     * Returns the authentication key.
     */
    protected static function getKey(): string {
        /*
         * ICDS_FORM_AUTH_KEY is used to generate a HMAC for the given entity reference.
         * SHA512 algorithm is used. Therefore, it is recommended that the key is 512-bit long.
         */
        $icdsFormAuthKey = AdvancedSettingsProvider::instance( 'ICDS_FORM_AUTH_KEY' );

        if ( $icdsFormAuthKey->isSet() && strlen( $icdsFormAuthKey->getValue() ) >= 64 ) {
            return $icdsFormAuthKey->getValue();
        } else{
            LoggerProvider::instance()->getLogger()->warning( __( 'ICDS_FORM_AUTH_KEY is not defined or not valid, using AUTH_KEY instead', 'integration-cds' ) );

            return AUTH_KEY;
        }
    }

}
