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

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provide encryption and decryption services
 */
class EncryptionService {

    use SingletonTrait;

    const ENCRYPTION_METHOD = 'aes-256-cbc';

    const DEFAULT_ENCRYPTION_KEY = 'abcdefghijklmnopqrstuvwxyz123456';

    /**
     * @var string
     */
    protected $key;

    /**
     * EncryptionService constructor.
     */
    public function __construct() {
        $icdsAuthKey = AdvancedSettingsProvider::instance( 'ICDS_AUTH_KEY' );

        if ( $icdsAuthKey->isSet() && strlen( $icdsAuthKey->getValue() ) >= 32 ) {
            $this->key = substr( $icdsAuthKey->getValue(), 0, 32 );
        } elseif ( defined( 'AUTH_KEY' ) && strlen( AUTH_KEY ) >= 32 ) {
            //LoggerProvider::instance()->getLogger()->warning( 'ICDS_AUTH_KEY is not defined or not valid, using AUTH_KEY instead' );

            $this->key = substr( AUTH_KEY, 0, 32 );
        } else {
            LoggerProvider::instance()->getLogger()->warning( __('Neither ICDS_AUTH_KEY nor AUTH_KEY is defined or is valid, using default encryption key instead', 'integration-cds') );

            $this->key = static::DEFAULT_ENCRYPTION_KEY;
        }
    }

    /**
     * Encrypt given string with defined algorithm
     *
     * @param string $string
     *
     * @return string
     */
    public function encrypt( string $string ): string {
        if ( !function_exists( 'openssl_random_pseudo_bytes' ) ) {
            return $string;
        }

        $log = LoggerProvider::instance()->getLogger();

        $ivLength = openssl_cipher_iv_length( self::ENCRYPTION_METHOD );
        if ( $ivLength === false ) {
            $log->critical( __( 'OpenSSL failed to return cipher IV length', 'integration-cds' ), [
                'cipher' => self::ENCRYPTION_METHOD,
            ] );

            return $string;
        }

        try {
            $iv = random_bytes( $ivLength );
        } catch ( \Exception $e ) {
            $log->alert( __( 'random_bytes() exception: ', 'integration-cds' ) . $e->getMessage(), [
                'exception' => $e,
            ] );

            return $string;
        }

        $ciphertext = openssl_encrypt( $string, self::ENCRYPTION_METHOD, $this->key, 0, $iv );

        return base64_encode( $iv ) . ':' . $ciphertext;
    }

    /**
     * Decrypt previously encrypted string according defined algorithm
     *
     * @param string $ivCiphertext
     *
     * @return string|null NULL returned on failure.
     */
    public function decrypt( string $ivCiphertext ): ?string {
        if ( !function_exists( 'openssl_random_pseudo_bytes' ) ) {
            return $ivCiphertext;
        }

        if ( strpos( $ivCiphertext, ':' ) === false ) {
            return $ivCiphertext;
        }

        [ $iv, $ciphertext ] = explode( ':', $ivCiphertext );

        $string = openssl_decrypt( $ciphertext, self::ENCRYPTION_METHOD, $this->key, 0, base64_decode( $iv ) );

        if ( $string === false ) {
            return null;
        }

        return $string;
    }
}
