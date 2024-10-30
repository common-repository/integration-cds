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
use AlexaCRM\Nextgen\ConnectionService;
use AlexaCRM\Nextgen\MetadataService;
use AlexaCRM\WebAPI\Exception;
use AlexaCRM\WebAPI\OData\AuthenticationException;

/**
 * Provides an endpoint to fetch entity metadata.
 */
class GetEntityMetadata extends AdministrativeEndpoint {

    /**
     * Endpoint name.
     */
    public string $name = 'entity_metadata/(?P<entityName>[a-z0-9_]+)';

    /**
     * List of supported HTTP methods.
     */
    public array $methods = [ 'GET' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        if ( !ConnectionService::instance()->isAvailable() ) {
            return new BadRequestResponse( 1, __( 'Connection to Dataverse has not been configured.', 'integration-cds' ) );
        }

        $entityName = $request->get_url_params()['entityName'];

        try {
            return new \WP_REST_Response( [
                'metadata' => MetadataService::instance()->getRegistry()->getDefinition( $entityName ),
            ] );

        } catch ( AuthenticationException $e ) {
            return new BadRequestResponse( 1, __( 'Authentication against Dataverse failed. Check connection settings.', 'integration-cds' ) );

        } catch ( Exception $e ) {
            return new \WP_Error( 1, $e->getMessage() );

        }
    }

}
