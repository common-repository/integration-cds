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
use AlexaCRM\Nextgen\API\BadRequestResponse;
use AlexaCRM\Nextgen\API\NoContentResponse;
use AlexaCRM\Nextgen\API\ServerErrorResponse;
use AlexaCRM\Nextgen\LoggerProvider;
use AlexaCRM\Nextgen\Webhooks\Runner;

/**
 * Provides an endpoint to register webhooks.
 */
class UpdateWebhook extends AdministrativeEndpoint {

    public string $name = 'webhooks/(?P<id>\d+)';

    public array $methods = [ 'PATCH' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $urlParams = $request->get_url_params();
        $bodyParams = $request->get_json_params();

        $id = $urlParams['id'] ?? null;

        if ( $id === null ) {
            return new BadRequestResponse( 'invalid-id', __( 'Webhook ID is invalid.', 'integration-cds' ) );
        }

        $enabled = $bodyParams['enabled'];

        $result = wp_update_post( [
            'ID' => $id,
            'post_status' => $enabled? Runner::ENABLED_STATUS : Runner::DISABLED_STATUS,
        ], true );

        if ( $result instanceof \WP_Error ) {
            LoggerProvider::instance()->getLogger()->error( __( "Failed to update webhook status.", 'integration-cds' ), [
                'webhook' => $id,
                'status' => $enabled? Runner::ENABLED_STATUS : Runner::DISABLED_STATUS,
            ] );

            return new ServerErrorResponse(
                'failed-update',
                __("Failed to update webhook {$id} status." . implode( ';', $result->get_error_messages() ) . '.', 'integration-cds')
            );
        }

        return new NoContentResponse();
    }
}
