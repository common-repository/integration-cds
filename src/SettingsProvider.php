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
 * Provides access to plugin settings.
 */
class SettingsProvider {

    use SingletonTrait;

    /**
     * Map of settings types. The key is the name.
     *
     * @var SettingsType[]
     */
    protected array $settingsMap = [];

    /**
     * Map of settings types. The key is the class name.
     *
     * Computed automatically after SettingsProvider::$settingsMap.
     *
     * @var SettingsType[]
     */
    protected array $settingsClassMap = [];

    /**
     * SettingsProvider constructor.
     */
    protected function __construct() {
        $this->updateSettingsMap();
    }

    /**
     * Builds a class -> settings type map for look-ups.
     *
     * @param SettingsType[] $settingsMap
     *
     * @return array
     */
    protected function buildClassMap( array $settingsMap ): array {
        $map = [];

        // TODO: Investigate empty $settingsMap.
        foreach ( $settingsMap as $settingType ) {
            $map[ $settingType->className ] = $settingType;
        }

        return $map;
    }

    /**
     * Provides strongly typed getter methods for different types of settings.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call( string $name, array $arguments ) {
        $isGetterCall = preg_match( '~get(.*?)Settings~', $name, $getterMatch );

        if ( !$isGetterCall ) {
            throw new \BadMethodCallException( 'Method `' . $name . '` not implemented' );
        }

        $settingsName = strtolower( $getterMatch[1] );

        return $this->getSettings( $settingsName );
    }

    /**
     * Return a settings of specified type
     *
     * @param string $settingsName
     *
     * @return Settings
     * @throws \InvalidArgumentException
     */
    public function getSettings( string $settingsName ): Settings {
        if ( !array_key_exists( $settingsName, $this->settingsMap ) ) {
            $this->updateSettingsMap();
        }

        if ( !array_key_exists( $settingsName, $this->settingsMap ) ) {
            throw new \InvalidArgumentException( 'Settings `' . $settingsName . '` not found' );
        }

        $type = $this->settingsMap[ $settingsName ];
        $data = get_option( $type->storageKey, '{}' ) ?: '{}';

        /** @var Settings $settings */
        $settings = call_user_func( [ $type->className, 'createFromArray' ], json_decode( $data, true ) );
        $settings->processAfterLoading();

        return $settings;
    }

    /**
     * @return SettingsType[]
     */
    public function getSettingsMap(): array {
        $this->updateSettingsMap();

        return $this->settingsMap;
    }

    /**
     * Persists the given settings object in the database.
     *
     * @param Settings $settings
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function persistSettings( Settings $settings ): bool {
        $className = get_class( $settings );

        if ( !array_key_exists( $className, $this->settingsClassMap ) ) {
            $this->updateSettingsMap();
        }

        if ( !array_key_exists( $className, $this->settingsClassMap ) ) {
            throw new \InvalidArgumentException( sprintf( __( 'Settings class %s is not supported', 'integration-cds' ), $className ) );
        }

        $type = $this->settingsClassMap[ $className ];

        /*
         * update_option() returns false if the new data is identical to the previous data,
         * thus not indicating a true failure.
         */
        $prev = $this->getSettings( $type->name );
        if ( $settings->isEqual( $prev ) ) {
            /**
             * Fires after a Settings object has been persisted in the database.
             *
             * @param Settings $settings
             * @param bool $hasChanged Whether the new settings differ from what's been stored in the database.
             */
            do_action( 'integration-cds/settings/updated', $settings, false );

            return true;
        }

        $settings->processBeforeSaving();
        $data = json_encode( $settings );
        $result = update_option( $type->storageKey, $data );

        /** This action is documented in integration-cds/src/SettingsProvider.php */
        do_action( 'integration-cds/settings/updated', $settings, true );

        return $result;
    }

    /**
     * Batch settings import using given array.
     *
     * @param array $settings array of new settings values.
     *
     * @return bool
     */
    public function importSettings( array $settings ): bool {
        /** @var Settings[] $collection */
        $collection = [];
        $settingsTypes = SettingsProvider::instance()->getSettingsMap();

        foreach ( $settingsTypes as $type ) {
            if ( !isset( $settings[ $type->name ] )){
                continue;
            }

            $prevSettings = (array) SettingsProvider::instance()->getSettings( $type->name );
            $mergedSettings = array_merge($prevSettings, $settings[ $type->name ]);

            /** @var Settings $settingsClassname */
            $settingsClassname = $type->className;
            $collection[] = $settingsClassname::createFromArray( $mergedSettings );
        }

        foreach ( $collection as $settingsInstance ) {
            try{
                if ( ! SettingsProvider::instance()->persistSettings( $settingsInstance ) ){
                    // TODO: properly handle errors for each settings type
                    return false;
                }
            }catch ( \InvalidArgumentException $e){
                // TODO: Implement rollback if anything fails.
                return false;
            }
        }

        return true;
    }

    /**
     * Updates the settings map with available settings.
     */
    protected function updateSettingsMap(): void {
        /**
         * Filters the list of registered settings.
         *
         * @param SettingsType[] $settingsMap
         */
        $this->settingsMap = apply_filters( 'integration-cds/settings/map', $this->settingsMap );
        $this->settingsClassMap = $this->buildClassMap( $this->settingsMap );
    }
}
