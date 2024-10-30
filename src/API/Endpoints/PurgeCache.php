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
use AlexaCRM\Nextgen\API\NoContentResponse;
use AlexaCRM\Nextgen\CacheProvider;

/**
 * Provides an endpoint to purge cache.
 *
 * @deprecated Use \AlexaCRM\Nextgen\DeleteCache class instead.
 */
class PurgeCache extends AdministrativeEndpoint {

    public string $name = 'purge_cache/?(?P<cachePool>[a-z0-9_]+)?';

    public array $methods = [ 'POST' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $specifiedCachePool = $request->get_url_params()['cachePool'] ?? null;

        if ( $specifiedCachePool === '__misc' ) {
            $excludedPools = [ 'metadata', 'fetchxml' ];

            $specifiedCachePool = array_filter(
                CacheProvider::instance()->getPoolNames(),
                fn( $pool ) => !in_array( $pool, $excludedPools )
            );
        }

        CacheProvider::instance()->clear( $specifiedCachePool );

        wp_schedule_single_event( time(), 'integration-cds/cache/warmup', [
            uniqid(), // Force WP scheduler to enqueue the job.
        ] );
        spawn_cron();

        return new NoContentResponse();
    }
}
