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

use AlexaCRM\Nextgen\Forms\Recaptcha\RecaptchaSettings;
use AlexaCRM\Nextgen\Forms\Recaptcha\RecaptchaSettingsAdapterInterface;
use AlexaCRM\Nextgen\Forms\Recaptcha\RecaptchaValidator;
use AlexaCRM\Nextgen\Forms\Recaptcha\SettingsAdapters\DefaultRecaptchaSettingsAdapter;

/**
 * Provide access to reCAPTCHA.
 */
class RecaptchaProvider {

    use SingletonTrait;

    /**
     * Default reCAPTCHA settings adapter identifier.
     */
    public const DEFAULT_ADAPTER = 'default';

    /**
     * Available settings adapters. Array of fully-qualified class names.
     * Each class must implement RecaptchaSettingsAdapterInterface.
     *
     * @var string[]
     */
    protected $adapters;

    /**
     * RecaptchaProvider constructor.
     */
    public function __construct() {
        $adapters = [
            self::DEFAULT_ADAPTER => Forms\Recaptcha\SettingsAdapters\DefaultRecaptchaSettingsAdapter::class,
        ];

        /**
         * Filters the list of available adapters for reCAPTCHA settings.
         *
         * @param array $adapters Associative array of reCAPTCHA settings adapters classes.
         */
        $this->adapters = apply_filters( 'integration-cds/recaptcha/settings/adapters', $adapters );
    }

    /**
     * Provides a validator to verify user's response.
     */
    public function getValidator(): RecaptchaValidator {
        return new RecaptchaValidator( $this->getSettings() );
    }

    /**
     * Returns a list of available adapters as array.
     *
     * ```
     * [ 'adapterKey' => 'Adapter Label' ]
     * ```
     *
     * @return array
     */
    public function getAvailableAdapters(): array {
        $result = [];

        foreach ( $this->adapters as $adapterKey => $adapterClassName ) {
            /** @var RecaptchaSettingsAdapterInterface $adapter */
            $adapter = new $adapterClassName();

            if ( $adapter->isAvailable() ) {
                $result[$adapterKey] = $adapter->getLabel();
            }
        }

        return $result;
    }

    /**
     * Retrieves reCAPTCHA settings, possibly coming from a 3rd party provider.
     *
     * @return RecaptchaSettings
     */
    public function getSettings(): RecaptchaSettings {
        $settings = ( new DefaultRecaptchaSettingsAdapter() )->getSettings();
        if ( $settings->adapterName === self::DEFAULT_ADAPTER ) {
            return $settings;
        }

        $adapterSettings = $this->getAdapterSettings( $settings->adapterName );

        return $adapterSettings ?? $settings;
    }

    /**
     * Return RecaptchaSettings from giving adapter or null if adapter is not available
     *
     * @param string $adapterName
     *
     * @return RecaptchaSettings|null
     */
    public function getAdapterSettings( string $adapterName ): ?RecaptchaSettings {
        /** @var RecaptchaSettingsAdapterInterface $adapter */
        $adapterClassName = $this->adapters[ $adapterName ];

        if ( !class_exists( $adapterClassName ) ) {
            LoggerProvider::instance()->getLogger()->alert( __( "reCAPTCHA settings adapter not found:", 'integration-cds' ) . "{$adapterClassName}." );
            return null;
        }

        $adapter = new $adapterClassName();
        if ( $adapter->isAvailable() ) {
            return $adapter->getSettings();
        }

        return null;
    }

    /**
     * Check if reCAPTCHA is available for use.
     */
    public function isAvailable(): bool {
        $settings = $this->getSettings();

        return !( empty( $settings->siteKey ) || empty( $settings->secretKey ) || empty( $settings->type ) );
    }

}
