<?php
/**
 * Copyright 2023 AlexaCRM
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

use AlexaCRM\Nextgen\API\Endpoint;
use AlexaCRM\Nextgen\ImageProxy;
use AlexaCRM\Xrm\EntityReference;

/**
 * Provides an endpoint to retrieve files.
 */
class GetImage extends Endpoint {

    public string $name = 'image';

    public array $methods = [ 'GET' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $proxy = new ImageProxy();

        $table = $request->get_param( 'table' ) ?? '';
        $id = $request->get_param( 'id' ) ?? '';
        $column = $request->get_param( 'column' ) ?? '';
        $isThumb = $request->get_param( 'isThumb' ) === '1';
        $headers = $request->get_param( 'h' ) ?? [];

        $allowedHeaders = [
            'Content-Disposition',
            'Content-Type',
            'Cache-Control',
        ];

        $headers = array_filter( $headers, fn( $k ) => in_array( $k, $allowedHeaders ), ARRAY_FILTER_USE_KEY );

        // For annotation, force documentbody.
        if ( $table === 'annotation' ) {
            $column = 'documentbody';
        }

        if ( !isset( $table, $id, $column ) ) {
            return new \WP_Error( 400 );
        }

        $responseCode = $proxy->serve( new EntityReference( $table, $id ), $column, $isThumb, $headers );

        return new \WP_REST_Response( [
            'response' => $responseCode,
        ] );
    }
}
