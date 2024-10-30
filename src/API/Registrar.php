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

namespace AlexaCRM\Nextgen\API;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers routes in the WP REST API and provides an extension point for plugin addons.
 */
class Registrar {

    /**
     * Registers the endpoints in WP REST API.
     */
    public function registerEndpoints(): void {
        $endpoints = $this->getEndpoints();

        foreach ( $endpoints as $endpoint ) {
            /**
             * @var Endpoint $endpointImpl
             */
            $endpointImpl = new $endpoint();

            /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
            register_rest_route( $endpointImpl->namespace, $endpointImpl->name, [
                'methods' => $endpointImpl->methods,
                'permission_callback' => [ $endpointImpl, 'isPermitted' ],
                'callback' => [ $endpointImpl, 'respond' ],
                'args' => $endpointImpl->getArgumentSchema(),
            ] );
        }
    }

    /**
     * Provides a collection of endpoint implementations (FQCNs).
     *
     * @return string[]
     */
    protected function getEndpoints(): array {
        $endpoints = [
            \AlexaCRM\Nextgen\API\Endpoints\GetNonce::class,
            \AlexaCRM\Nextgen\API\Endpoints\CommitSettings::class,
            \AlexaCRM\Nextgen\API\Endpoints\GetSettings::class,
            \AlexaCRM\Nextgen\API\Endpoints\CheckConnection::class,
            \AlexaCRM\Nextgen\API\Endpoints\ResetConnection::class,
            \AlexaCRM\Nextgen\API\Endpoints\GetConnectionStatus::class,
            \AlexaCRM\Nextgen\API\Endpoints\CheckPremium::class,
            \AlexaCRM\Nextgen\API\Endpoints\GetEntityMetadata::class,
            \AlexaCRM\Nextgen\API\Endpoints\PurgeCache::class,
            \AlexaCRM\Nextgen\API\Endpoints\SubmitCustomForm::class,
            \AlexaCRM\Nextgen\API\Endpoints\CheckRecaptcha::class,
            \AlexaCRM\Nextgen\API\Endpoints\GetRecaptchaSettings::class,
            \AlexaCRM\Nextgen\API\Endpoints\GetWebhooks::class,
            \AlexaCRM\Nextgen\API\Endpoints\RegisterWebhook::class,
            \AlexaCRM\Nextgen\API\Endpoints\DeleteWebhook::class,
            \AlexaCRM\Nextgen\API\Endpoints\UpdateWebhook::class,
            \AlexaCRM\Nextgen\API\Endpoints\InstallAddon::class,
            \AlexaCRM\Nextgen\API\Endpoints\ActivateAddon::class,
            \AlexaCRM\Nextgen\API\Endpoints\DeactivateAddon::class,
            \AlexaCRM\Nextgen\API\Endpoints\ResetUserPassword::class,
            \AlexaCRM\Nextgen\API\Endpoints\GetResetUserPasswordLink::class,
            \AlexaCRM\Nextgen\API\Endpoints\DeleteCache::class,
            \AlexaCRM\Nextgen\API\Endpoints\GetFile::class,
            \AlexaCRM\Nextgen\API\Endpoints\GetImage::class,

            // V2 endpoints
            \AlexaCRM\Nextgen\API\EndpointsV2\UpdateLogVerbosity::class,
            \AlexaCRM\Nextgen\API\EndpointsV2\UpdateLogSettings::class,
            \AlexaCRM\Nextgen\API\EndpointsV2\DownloadLogs::class,
            \AlexaCRM\Nextgen\API\EndpointsV2\RemoveLogs::class,
            \AlexaCRM\Nextgen\API\EndpointsV2\UpdateAdvancedSettings::class,
        ];

        /**
         * Filters the collection of available API endpoints.
         *
         * @param array $endpoints Collection of FQCNs implementing API endpoints.
         */
        return apply_filters( 'integration-cds/api/endpoints', $endpoints );
    }

}
