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
 * Describes the Dataverse connection settings, including deployment type and authentication details.
 */
class ConnectionSettings extends Settings {

    /**
     * Describes the supported authentication flows per deployment configuration,
     * as well as concrete AuthenticationSettings types which represent the specific flow credentials.
     */
    const INSTANCE_SUPPORT = [
        'online' => [
            's2s-secret' => 'AlexaCRM\Nextgen\OnlineS2SSecretAuthenticationSettings',
            's2s-certificate' => 'AlexaCRM\Nextgen\OnlineS2SCertificateAuthenticationSettings',
        ],
    ];

    /**
     * Organization URL.
     *
     * @var string
     */
    public ?string $instanceURI = null;

    /**
     * Deployment type.
     *
     * @see InstanceType
     */
    public ?string $instanceType = null;

    /**
     * Authentication type.
     *
     * @var string
     * @see AuthenticationType
     */
    public string $authenticationType;

    public $certificatePathMethod;

    /**
     * Authentication settings.
     */
    public ?AuthenticationSettings $authenticationSettings = null;

    /**
     * Whether to skip TLS certificate verification. Default is false.
     *
     * @var bool
     */
    public bool $skipCertificateVerification = false;

    /**
     * Ensures deep cloning.
     */
    public function __clone() {
        if ( $this->authenticationSettings !== null ) {
            $this->authenticationSettings = clone $this->authenticationSettings;
        }
    }

    /**
     * Creates a new connection settings instance from the given data.
     *
     * @param array $data
     *
     * @return ConnectionSettings
     * @throws \InvalidArgumentException
     */
    public static function createFromArray( array $data ): ConnectionSettings {
        $instance = new static();

        if ( isset( $data['instanceType'] )
             && !array_key_exists( $data['instanceType'], static::INSTANCE_SUPPORT ) ) {
            throw new \InvalidArgumentException( sprintf( __( 'Instance type "%s" is not supported', 'integration-cds' ), $data['instanceType'] ) );
        }

        if ( isset( $data['authenticationType'] )
             && !array_key_exists( $data['authenticationType'], static::INSTANCE_SUPPORT[ $data['instanceType'] ] ) ) {
            throw new \InvalidArgumentException( sprintf( __( 'Authentication type "%s" is not supported for %s deployments', 'integration-cds' ), $data['authenticationType'], $data['instanceType'] ) );
        }

        foreach ( $data as $key => $value ) {
            if ( $key === 'authenticationSettings' && !empty( $value ) ) {

                if ( $data['instanceType'] === null || $data['authenticationType'] === null ){
                    $instance->authenticationSettings = null;
                    continue;
                }

                /** @var AuthenticationSettings $className */
                $className = static::INSTANCE_SUPPORT[ $data['instanceType'] ][ $data['authenticationType'] ];

                $instance->authenticationSettings = $className::createFromArray( $value );
                continue;
            }

            $instance->{$key} = $value;
        }

        return $instance;
    }

    /**
     * Encrypt authentication settings.
     */
    public function processBeforeSaving(): void {
        /** @var ConnectionSettings $prevSettings */
        $prevSettings = SettingsProvider::instance()->getSettings( 'connection' );

        if ( $this->authenticationSettings === null ) {
            return;
        }

        if($prevSettings->authenticationSettings !== null) {
            $this->authenticationSettings->restoreHiddenFieldsFromSettings( $prevSettings->authenticationSettings );
        }

        $this->authenticationSettings->encrypt();
    }

    /**
     * Decrypt authentication settings.
     */
    public function processAfterLoading(): void {
        if ( $this->authenticationSettings === null ) {
            return;
        }

        $this->authenticationSettings->decrypt();
    }

    /**
     * Tells whether two Settings objects are equal.
     *
     * @param Settings $s2
     *
     * @return bool
     */
    public function isEqual( Settings $s2 ): bool {
        /** @var ConnectionSettings $s2 */
        if ( $this->authenticationSettings === null || $s2->authenticationSettings === null ) {
            return parent::isEqual( $s2 );
        }

        $s1 = clone $this;
        $s1->authenticationSettings->restoreHiddenFieldsFromSettings( $s2->authenticationSettings );

        $str = json_encode( $s1 );
        $str2 = json_encode( $s2 );

        return $str === $str2;
    }

}
