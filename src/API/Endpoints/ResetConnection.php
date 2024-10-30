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

namespace AlexaCRM\Nextgen\API\Endpoints;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\API\AdministrativeEndpoint;
use AlexaCRM\Nextgen\API\BadRequestResponse;
use AlexaCRM\Nextgen\CacheProvider;
use AlexaCRM\Nextgen\ConnectionSettings;
use AlexaCRM\Nextgen\EncryptedDbCachePool;
use AlexaCRM\Nextgen\OnlineS2SCertificateAuthenticationSettings;
use AlexaCRM\Nextgen\OnlineS2SSecretAuthenticationSettings;
use AlexaCRM\Nextgen\SettingsProvider;

/**
 * Provides an endpoint to reset connection to Dataverse.
 */
class ResetConnection extends AdministrativeEndpoint {

    public string $name = 'reset_connection';

    public array $methods = [ 'POST' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $params = $request->get_json_params();
        if ( !empty( $params ) && $params['authenticationType'] === OnlineS2SCertificateAuthenticationSettings::name && $params['certificatePathMethod'] === 'file_upload' ) {
            $this->removeCertificate( $params['certificatePath'] );
        }
        $settings = ConnectionSettings::createFromArray( [] );

        $settings->authenticationSettings = OnlineS2SSecretAuthenticationSettings::createFromArray( [] );

        SettingsProvider::instance()->persistSettings( $settings );

        // Clear toolkit pool
        $pool = new EncryptedDbCachePool( 'toolkit_conn' );
        $pool->clear();
        CacheProvider::instance()->clear();

        return new \WP_REST_Response( [
            'settings' => $settings,
        ] );
    }

    /**
     * @param string $path
     * Removes certificate file by provided path
     * @return void
     */
    private function removeCertificate( string $path ): void {
        if ( !empty( $path ) && file_exists( $path ) ) {
            unlink( $path );
        }
    }
}
