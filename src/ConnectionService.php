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

use AlexaCRM\WebAPI\OData\Exception;
use AlexaCRM\WebAPI\OData\Settings as ClientSettings;

/**
 * Provides access to the configured Dataverse instance.
 */
class ConnectionService {

    use SingletonTrait;

    protected ?WebApiClient $client = null;

    /**
     * API version that is used when querying Dataverse.
     *
     * The minimum supported version is reported before the first connection to Dataverse.
     *
     * @var string
     */
    public string $apiVersion = '9.1';

    public function getClient(): ?WebApiClient {
        if ( $this->client instanceof WebApiClient ) {
            return $this->client;
        }

        $factory = new ConnectionFactory();
        /** @var ConnectionSettings $settings */
        $settings = SettingsProvider::instance()->getSettings( 'connection' );

        try {
            $client = $factory->createFromSettings( $settings );
        } catch ( DeploymentNotSupportedException $e ) {
            return null;
        }

        $this->client = $client;
        $this->apiVersion = $client->getClient()->getSettings()->apiVersion;

        return $client;
    }

    /**
     * Tells whether a Dataverse connection can be established.
     *
     * Checks configuration to be sufficient to establish a connection,
     * but does not actually make a call to Dataverse to verify availability.
     * For the latter, see ConnectionService::isOperating().
     */
    public function isAvailable(): bool {
        return ( $this->getClient() instanceof WebApiClient );
    }

    /**
     * Checks whether a Dataverse connection is established.
     *
     * Makes a WhoAmI() call to Web API to ensure.
     */
    public function isOperating(): bool {
        if ( !$this->isAvailable() ) {
            return false;
        }

        try {
            $odata = $this->getClient()->getClient();
            $whoAmIResponse = $odata->executeFunction( 'WhoAmI' );
        } catch ( Exception $e ) {
            return false;
        }

        return isset( $whoAmIResponse->UserId );
    }

    /**
     * Provides active settings applied by the client.
     *
     * @return ClientSettings
     */
    public function getResolvedSettings(): ClientSettings {
        return $this->getClient()->getClient()->getSettings();
    }

}
