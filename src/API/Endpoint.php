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

namespace AlexaCRM\Nextgen\API;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Represents a WP REST API endpoint.
 */
abstract class Endpoint {

    /**
     * Default v2 API namespace.
     */
    public const DEFAULT_NS = 'integration-cds/v1';

    /**
     * Endpoint namespace.
     */
    public string $namespace = Endpoint::DEFAULT_NS;

    /**
     * Endpoint name.
     *
     * The property must be initialized in the implementing class.
     */
    public string $name;

    /**
     * List of supported HTTP methods.
     *
     * @var string[]
     */
    public array $methods = [ 'GET' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public abstract function respond( \WP_REST_Request $request );

    /**
     * Checks whether the request can be processed in the current context.
     *
     * Acts as a permission callback for the endpoint.
     *
     * Allows every request by default.
     *
     * @return bool
     */
    public function isPermitted(): bool {
        return true;
    }

    /**
     * Returns the schema for arguments of the endpoint.
     *
     * @return array
     */
    public function getArgumentSchema(): array {
        return [];
    }

}
