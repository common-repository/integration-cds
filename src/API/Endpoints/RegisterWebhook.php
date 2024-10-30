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
use AlexaCRM\Nextgen\API\CreatedResponse;
use AlexaCRM\Nextgen\API\ServerErrorResponse;
use AlexaCRM\Nextgen\LoggerProvider;
use AlexaCRM\Nextgen\Webhooks\Runner;
use WP_Error;

/**
 * Provides an endpoint to register webhooks.
 */
class RegisterWebhook extends AdministrativeEndpoint {

    public string $name = 'webhooks';

    public array $methods = [ 'POST' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $req = $request->get_json_params();
        $topic = $req['topic'] ?? null;
        if ( $topic === null ) {
            return new BadRequestResponse( 'no-topic', __( 'Webhook topic is a mandatory field.', 'integration-cds' ) );
        }

        $target = $req['target'] ?? null;
        if ( $target === null ) {
            return new BadRequestResponse( 'no-target', __( 'Webhook target URL is a mandatory field.', 'integration-cds' ) );
        }

        $p = wp_insert_post( [
            'post_type' => Runner::POST_TYPE,
            'post_status' => 'publish',
            'meta_input' => [
                Runner::TOPIC_KEY => $topic,
                Runner::TARGET_KEY => $target,
            ],
        ], true );

        if ( $p instanceof WP_Error ) {
            LoggerProvider::instance()->getLogger()->error( __( "Failed to register the webhook.", 'integration-cds' ), [
                'error' => $p->get_error_data(),
            ] );

            return new ServerErrorResponse(
                'failed-register',
                __( 'Failed to register the webhook. ' . implode( '; ', $p->get_error_messages() ), 'integration-cds' )
            );
        }

        $deleteUrl = rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->name, $p ) );

        return new CreatedResponse( [ 'Location' => $deleteUrl ] );
    }
}
