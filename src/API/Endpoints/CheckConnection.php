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

namespace AlexaCRM\Nextgen\API\Endpoints;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Cache\NullAdapter;
use AlexaCRM\Nextgen\API\AdministrativeEndpoint;
use AlexaCRM\Nextgen\API\BadRequestResponse;
use AlexaCRM\Nextgen\ConnectionFactory;
use AlexaCRM\Nextgen\ConnectionSettings;
use AlexaCRM\Nextgen\DeploymentNotSupportedException;
use AlexaCRM\Nextgen\LoggerProvider;
use AlexaCRM\Nextgen\OnlineS2SCertificateAuthenticationSettings;

/**
 * Provides an endpoint to check connection to Dataverse with the given connection settings.
 */
class CheckConnection extends AdministrativeEndpoint {

    /**
     * @var string
     */
    public string $name = 'check_connection';

    /**
     * @var string[]
     */
    public array $methods = [ 'POST' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     * @throws \Exception
     */
    public function respond( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $file = $request->get_file_params();
        $params = $request->get_json_params();

        if ( $params['authenticationType'] === OnlineS2SCertificateAuthenticationSettings::name ) {
            $params['authenticationSettings'] = json_decode( $params['authenticationSettings'], true );
            if ( !empty( $file ) ) {
                $certificatePath = $this->uploadCertificate( $file );
                $params['authenticationSettings']['certificatePath'] = $certificatePath;
            }
        }

        $settings = ConnectionSettings::createFromArray( $params );

        $factory = new ConnectionFactory();
        try {
            $client = $factory->createFromSettings( $settings );
        } catch ( DeploymentNotSupportedException $e ) {
            return new BadRequestResponse( 1, __( 'Provided deployment type is not supported.', 'integration-cds' ) );
        }

        // Disable cache for the temporary client, differentiate the logs.
        $clientSettings = $client->getClient()->getSettings();
        $clientSettings->cachePool = new NullAdapter();
        $clientSettings->setLogger( LoggerProvider::instance()->getNewLogger( 'webapi-testconn' ) );

        try {
            $response = (array)$client->getClient()->executeFunction( 'WhoAmI' );
        } catch ( \Exception $e ) {
            return new BadRequestResponse( 1, sprintf( __( 'Failed to establish a connection to CRM. %s', 'integration-cds' ), $e->getMessage() ) );
        }

        if ( array_key_exists( 'UserId', $response ) && count( $response ) === 3 ) {
            return new \WP_REST_Response( empty( $file ) ? null : $certificatePath, 200 );
        }

        LoggerProvider::instance()->getLogger()->error( __( 'Unexpected response to WhoAmI() from Dataverse', 'integration-cds' ), [ 'WhoAmIResponse' => $response ] );

        return new BadRequestResponse( 1, __( 'Unexpected response from Dataverse. Check Dataverse user permissions.', 'integration-cds' ) );
    }

    /**
     * Validates and uploads certificate file.
     *
     * @param $file
     *
     * @return string
     * @throws \Exception
     */
    private function uploadCertificate( $file ): string {
        $target_file = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'integration-cds' . DIRECTORY_SEPARATOR . basename( $file["certificate"]["name"] );
        $file_type = strtolower( pathinfo( $target_file, PATHINFO_EXTENSION ) );
        if ( $file_type !== 'pfx' ) {
            throw new \Exception( "Invalid extension. You must upload the certificate in an encrypted format (with a .pfx extension)." );
        }

        if ( move_uploaded_file( $file["certificate"]["tmp_name"], $target_file ) ) {
            return $target_file;
        } else {
            throw new \Exception( "Can't upload certificate in $target_file" );
        }
    }
}
