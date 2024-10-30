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
 * Represents settings for a specific authentication method.
 */
abstract class AuthenticationSettings {

    use CreateFromArrayTrait;

    const HIDDEN_FIELD_PLACEHOLDER = "";

    /**
     * Return array of fields to be encrypted
     *
     * @return string[];
     */
    abstract protected function getEncryptedFields(): array;

    /**
     * Replace fields that should be encrypted with static::HIDDEN_FIELD_PLACEHOLDER string
     */
    public function hideSecretFields(): void {
        foreach ( $this->getEncryptedFields() as $field ) {
            $this->$field = static::HIDDEN_FIELD_PLACEHOLDER;
        }
    }

    /**
     * Restored fields replaced with self::HIDDEN_FIELD_PLACEHOLDER string from given AuthenticationSettings
     *
     * @param AuthenticationSettings $settings
     */
    public function restoreHiddenFieldsFromSettings( AuthenticationSettings $settings ): void {
        foreach ( $this->getEncryptedFields() as $field ) {
            if ( $this->$field === AuthenticationSettings::HIDDEN_FIELD_PLACEHOLDER ) {
                $this->$field = $settings->$field;
            }
        }
    }

    /**
     * Encrypt fields defined by self::getEncryptedFields()
     */
    public function encrypt(): void {
        foreach ( $this->getEncryptedFields() as $field ) {
            if ( !empty($this->$field) ){
                $this->$field = EncryptionService::instance()->encrypt( $this->$field );
            }
        }
    }

    /**
     * Decrypt fields defined by self::getEncryptedFields()
     */
    public function decrypt(): void {
        foreach ( $this->getEncryptedFields() as $field ) {
            $this->$field = EncryptionService::instance()->decrypt( $this->$field );
        }
    }

}
