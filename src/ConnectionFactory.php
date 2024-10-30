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

use AlexaCRM\WebAPI\OData\Client;
use AlexaCRM\WebAPI\OData\OnlineSettings;
use AlexaCRM\WebAPI\OData\TransferOptionMiddleware;
use GuzzleHttp\RequestOptions;
use WP_HTTP_Proxy;

/**
 * Creates Web API Toolkit client instances for consumers.
 */
class ConnectionFactory {

    /**
     * @param ConnectionSettings $connSettings
     *
     * @return WebApiClient
     * @throws DeploymentNotSupportedException
     */
    public function createFromSettings( ConnectionSettings $connSettings ): WebApiClient {
        switch ( $connSettings->instanceType ) {
            case 'online':
                $tuple = $this->createOnlineTuple( $connSettings );
                break;
            default:
                throw new DeploymentNotSupportedException( sprintf( __( 'Instance type "%s" is not supported', 'integration-cds' ), $connSettings->instanceType ) );
        }

        /**
         * @var \AlexaCRM\WebAPI\OData\Settings $settings
         * @var \AlexaCRM\WebAPI\OData\AuthMiddlewareInterface $authMiddleware
         */
        [ $settings, $authMiddleware ] = $tuple;

        $clientMiddlewares = [];

        $proxyMiddleware = $this->getProxySettingsMiddleware();

        if ( $proxyMiddleware !== null ) {
            $clientMiddlewares[] = $proxyMiddleware;
        }

        $settings->caBundlePath = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
        $settings->tlsVerifyPeers = !$connSettings->skipCertificateVerification;

        $logger = LoggerProvider::instance()->getNewLogger( 'webapi-toolkit' );
        $settings->logger = $logger;

        $cache = CacheProvider::instance()->providePool( 'webapi' );
        $settings->cachePool = $cache;

        $settings->apiVersion = AdvancedSettingsProvider::instance( 'ICDS_SDK_VERSION' )->getValue();

        $client = new Client( $settings, $authMiddleware, ...$clientMiddlewares );

        return new WebApiClient( $client );
    }

    /**
     * Creates a Settings / Middleware tuple for Online deployments.
     *
     * @param ConnectionSettings $connSettings
     *
     * @return array
     * @throws DeploymentNotSupportedException
     */
    private function createOnlineTuple( ConnectionSettings $connSettings ): array {
        if ( !( $connSettings->authenticationSettings instanceof OnlineS2SSecretAuthenticationSettings ) && !( $connSettings->authenticationSettings instanceof OnlineS2SCertificateAuthenticationSettings) ) {
            throw new DeploymentNotSupportedException( __( 'Authentication type not supported.', 'integration-cds' ) );
        }

        $settings = new OnlineSettings();
        $settings->instanceURI = $connSettings->instanceURI ?? '';
        $settings->applicationID = $connSettings->authenticationSettings->applicationID ?? '';
        $settings->applicationSecret = $connSettings->authenticationSettings->applicationSecret ?? '';
        $settings->certificatePath = $connSettings->authenticationSettings->certificatePath ?? '';
        $settings->passphrase = $connSettings->authenticationSettings->passphrase ?? '';

        $middleware = new OnlineAuthMiddleware( $settings );

        return [ $settings, $middleware ];
    }

    /**
     * Creates middleware for adding proxy settings.
     *
     * @return TransferOptionMiddleware|null
     */
    private function getProxySettingsMiddleware(): ?TransferOptionMiddleware {
        $wpProxy = new WP_HTTP_Proxy();
        $proxyString = null;

        if ( $wpProxy->is_enabled() ) {
            $proxyString = "http://";

            if ( $wpProxy->use_authentication() ) {
                $proxyString .= $wpProxy->authentication() . '@';
            }

            $proxyString .= $wpProxy->host() . ':' . $wpProxy->port();
        }

        /**
         * Allows modifying connection proxy settings.
         *
         * @param string $proxyString
         */
        $proxyString = apply_filters( 'integration-cds/proxy', $proxyString );

        if ( $proxyString === null ) {
            return null;
        }

        return new TransferOptionMiddleware( RequestOptions::PROXY, $proxyString);
    }

}
