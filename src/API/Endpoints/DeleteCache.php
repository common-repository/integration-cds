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

use AlexaCRM\Nextgen\API\AdministrativeEndpoint;
use AlexaCRM\Nextgen\API\BadRequestResponse;
use AlexaCRM\Nextgen\API\NoContentResponse;
use AlexaCRM\Nextgen\API\ServerErrorResponse;
use AlexaCRM\Nextgen\CacheProvider;
use AlexaCRM\Nextgen\LoggerProvider;

/**
 * Provides an endpoint to delete cache.
 */
class DeleteCache extends AdministrativeEndpoint {

    public string $name = 'cache/?(?P<cachePool>[a-z0-9_-]+)?/?(?P<cacheItem>[a-zA_Z0-9_-]+)?/?(?P<cacheSubItem>[a-zA-Z0-9 %_-]+)?';

    public array $methods = [ 'DELETE' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $cachePool = $request->get_url_params()['cachePool'] ?? null;
        $cacheItem = $request->get_url_params()['cacheItem'] ?? null;
        $cacheSubItem = $request->get_url_params()['cacheSubItem'] ?? null;

        // aliases
        if ( $cachePool === 'entity' ) {
            $cachePool = 'entityrecords';
        }

        /**
         * Filters a list of cache delete handlers.
         *
         * @param array $handlers A list of handlers where key is a cache pool name and value is a callable.
         */
        $cacheHandlers = apply_filters( 'integration-cds/cache/delete-handlers', [
            'fetchxml' => [ $this, 'clearFetchxmlCache' ],
            'entityrecords' => [ $this, 'clearEntityRecordsCache' ],
        ] );

        if ( !empty( $cachePool ) && is_callable( $cacheHandlers[ $cachePool ] ) ) {
            $result = $cacheHandlers[ $cachePool ]( $cacheItem, $cacheSubItem );

            if ( $result instanceof \WP_Error ) {
                return $result;
            }

            if ( $result === false ) {
                return new ServerErrorResponse( 'integration-cds/cache-delete', 'Failed to clear cache.', [
                    'pool' => $cachePool,
                    'item' => $cacheItem,
                ] );
            }
        } elseif ( $cachePool === '__misc' ) {
            $miscPools = array_filter(
                CacheProvider::instance()->getPoolNames(),
                fn( $pool ) => !in_array( $pool, array_keys( $cacheHandlers ) )
            );
            CacheProvider::instance()->clear( $miscPools );
        } elseif ( $cachePool === null ) {
            $this->clearFetchxmlCache( $cacheItem );
            $this->clearEntityRecordsCache( $cacheItem );
            CacheProvider::instance()->clear();
        }

        // Force cache refresh
        wp_schedule_single_event( time(), 'integration-cds/cache/warmup', [
            uniqid(), // Force WP scheduler to enqueue the job.
        ] );
        spawn_cron();

        return new NoContentResponse();
    }

    /**
     * Deletes specified item from 'fetchxml' cache pool.
     *
     * @param $cacheItem
     *
     * @return bool
     */
    protected function clearFetchxmlCache( $cacheItem ): bool {
        return $this->clearPool( 'fetchxml', $cacheItem );
    }

    /**
     * Deletes specified item from 'entityrecords' cache pool.
     *
     * @param $cacheItem
     *
     * @return bool
     */
    protected function clearEntityRecordsCache( $cacheItem ): bool {
        return $this->clearPool( 'entityrecords', $cacheItem );
    }

    /**
     * @param string $poolName
     * @param string $cacheItem
     *
     * @return bool
     */
    private function clearPool( string $poolName, $cacheItem ): bool {
        $logger = LoggerProvider::instance()->getLogger();

        $cacheItemNormal = str_replace( [ '/', '\\' ], '', (string)$cacheItem );

        if ( $cacheItem !== null ) {
            $pools = CacheProvider::instance()->getPools( "/\\b{$poolName}-{$cacheItemNormal}\\b/" );
        } else {
            $pools = CacheProvider::instance()->getPools( "/{$poolName}/" );
        }

        if ( empty( $pools ) ) {
            $poolFullname = $poolName . ( $cacheItem !== null ? '-' . $cacheItemNormal : '' );

            $logger->alert( "Cache pool $poolFullname not found." );

            return false;
        }

        foreach ( $pools as $pool ) {
            if ( !$pool->clear() ) {
                return false;
            }
        }

        return true;
    }
}
