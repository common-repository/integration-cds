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

namespace AlexaCRM\Nextgen\API\Endpoints;

use AlexaCRM\Nextgen\API\AdministrativeEndpoint;
use AlexaCRM\Nextgen\API\NoContentResponse;
use AlexaCRM\Nextgen\API\ServerErrorResponse;
use AlexaCRM\Nextgen\LoggerProvider;
use WP_Error;

/**
 * Provides an endpoint to register webhooks.
 */
class DeleteWebhook extends AdministrativeEndpoint {

    public string $name = 'webhooks/(?P<id>\d+)';

    public array $methods = [ 'DELETE' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $params = $request->get_url_params();
        $id = $params['id'];

        $result = wp_delete_post( $id );

        if ( !$result instanceof \WP_Post ) {
            LoggerProvider::instance()->getLogger()->error( __( "Failed to delete the webhook.", 'integration-cds' ), [
                'webhook' => $id,
            ] );

            return new ServerErrorResponse( 'failed-delete', __( "Failed to delete the webhook {$id}.", 'integration-cds' ) );
        }

        return new NoContentResponse();
    }
}
