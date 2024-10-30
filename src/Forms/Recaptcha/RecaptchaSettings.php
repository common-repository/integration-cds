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

use AlexaCRM\Nextgen\RecaptchaProvider;
use AlexaCRM\Nextgen\Settings;

/**
 * Describes the settings used for requesting reCAPTCHA verification.
 */
class RecaptchaSettings extends Settings {

    /**
     * The list of settings which cannot be overwritten.
     */
    const READONLY = [
        'adapterName',
        'siteKey',
        'secretKey',
        'type',
    ];

    public ?string $adapterName = RecaptchaProvider::DEFAULT_ADAPTER;

    public ?string $siteKey = null;

    public ?string $secretKey = null;

    /**
     * @var string|null
     * @see Type
     */
    public ?string $type = null;

    /**
     * @var string|null
     * @see Theme
     */
    public ?string $theme = Theme::LIGHT;

    /**
     * @var string|null
     * @see Size
     */
    public ?string $size = Size::NORMAL;

    /**
     * @var string|null
     * @see Badge
     */
    public ?string $badge = null;

    /**
     * Optional not unique identifier of reCAPTCHA instance.
     *
     * @var string|null
     */
    public ?string $action = null;

    /**
     * @var float|null
     */
    public ?float $scoreThreshold = 0.5;

    /**
     * Creates a new class instance from the given data. Nulls a few fields if they are empty strings.
     *
     * @param array $data
     *
     * @return RecaptchaSettings
     */
    public static function createFromArray( array $data ): RecaptchaSettings {
        $settings = new RecaptchaSettings();

        $nullableFields = [ 'siteKey', 'secretKey' ];
        foreach ( $data as $key => $value ) {
            if ( in_array( $key, $nullableFields, true ) && is_string( $value ) && trim( $value ) === '' ) {
                $settings->{$key} = null;
                continue;
            }

            if ( $key === 'scoreThreshold' ) {
                $settings->{$key} = (float)$value;
                continue;
            }

            $settings->{$key} = $value;
        }

        if ( $settings->adapterName === null ) {
            $settings->adapterName = RecaptchaProvider::DEFAULT_ADAPTER;
        }

        return $settings;
    }

    public function __unserialize( array $something ): void {
        // scoreThreshold could've been stored as a string in the database. Not doing this conversion is fatal.
        if ( isset( $something['scoreThreshold'] ) && is_numeric( $something['scoreThreshold'] ) ) {
            $this->scoreThreshold = (float)$something['scoreThreshold'];
        }
    }

}
