<?php
/**
 * Copyright 2021 AlexaCRM
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
 * Provide access to advanced settings defined via constants or Advanced Settings UI.
 */
class AdvancedSettingsProvider {

    /**
     * Setting defined as a constant.
     */
    public const SOURCE_CONST = 'const';

    /**
     * Setting defined in admin UI.
     */
    public const SOURCE_UI = 'ui';

    /**
     * Map of all available advanced settings.
     *
     * @var AdvancedSetting[]
     */
    public static array $registeredSettings = [];

    /**
     * Selected settings item.
     *
     * @var AdvancedSetting|null
     */
    protected ?AdvancedSetting $setting = null;

    /**
     * Selected setting value.
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Whether the selected setting has been defined.
     *
     * @var bool
     */
    protected bool $isSet = false;

    /**
     * The way selected setting is defined.
     *
     * @var string|null
     */
    protected ?string $source = null;

    /**
     * @var static[]
     */
    protected static array $instancePool = [];

    /**
     * AdvancedSettingsProvider constructor.
     *
     * @param string $settingKey
     */
    public function __construct( string $settingKey ) {
        if ( empty( static::$registeredSettings ) ) {
            /**
             * Filters the list of registered advanced settings.
             *
             * @param AdvancedSetting[] $settings
             */
            static::$registeredSettings = apply_filters( 'integration-cds/settings/advanced', [] );
        }

        if ( !array_key_exists( $settingKey, static::$registeredSettings ) ) {
            return;
        }

        $this->setting = static::$registeredSettings[ $settingKey ];

        if ( defined( $this->setting->key ) ) {
            $this->isSet = true;
            $this->source = static::SOURCE_CONST;
            $this->value = $this->getConstantValue( $this->setting->key );
        } else if ( ( $saved = $this->retrieveSetting( $this->setting->key ) ) !== null ) {
            $this->isSet = true;
            $this->source = static::SOURCE_UI;
            $this->value = $saved;
        }
    }

    /**
     * Provides access to the specific advanced setting.
     *
     * @param string $settingsKey Setting key to provide access for.
     *
     * @return static
     */
    public static function instance( string $settingsKey ) {
        if (
            !isset( static::$instancePool[ $settingsKey ] ) ||
            !( static::$instancePool[ $settingsKey ] instanceof static )
        ) {
            static::$instancePool[ $settingsKey ] = new static( $settingsKey );
        }

        return static::$instancePool[ $settingsKey ];
    }

    /**
     * Returns setting value if any.
     *
     * @param string $settingKey
     *
     * @return mixed
     */
    protected function retrieveSetting( string $settingKey ) {
        /** @var AdvancedSettings $savedSettings */
        $savedSettings = SettingsProvider::instance()->getSettings( AdvancedSettings::SETTINGS_TYPE_NAME );

        if ( array_key_exists( $settingKey, $savedSettings->settings ) ) {
            return $savedSettings->settings[ $settingKey ];
        }

        return null;
    }

    /**
     * Whether advanced setting is defined.
     *
     * @return bool
     */
    public function isSet(): bool {
        return $this->isSet;
    }

    /**
     * Returns the setting value if defined of null otherwise.
     *
     * @return mixed
     */
    public function getValue() {
        return $this->value ?? $this->getDefaultValue();
    }

    /**
     * Determines whether setting is defined and its value is equivalent to the true value.
     *
     * @return bool
     */
    public function isTrue(): bool {
        return $this->isSet() && ( $this->value === true || $this->value === 1 );
    }

    /**
     * Return the type of setting definition or null if not defined.
     *
     * @return string|null
     */
    public function getSource(): ?string {
        return $this->source;
    }

    /**
     * Returns default value for the selected setting.
     *
     * @return mixed
     */
    public function getDefaultValue() {
        return $this->setting->default;
    }

    /**
     * Returns defined constant value for the given key with typecasting if needed.
     *
     * @param string $key
     *
     * @return mixed
     */
    private function getConstantValue( string $key ) {
        $setting = static::$registeredSettings[ $key ];
        $value = constant( $this->setting->key );

        if ( $setting->type === AdvancedSetting::TYPE_BOOLEAN ) {
            if ( $value === 1 || $value === "1" ) {
                $value = true;
            }
        }

        return $value;
    }
}
