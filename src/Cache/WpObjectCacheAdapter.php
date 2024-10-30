<?php
/**
 * Copyright 2018-2019 AlexaCRM
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

namespace AlexaCRM\Nextgen\Cache;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use Symfony\Component\Cache\Adapter\AbstractAdapter;

/**
 * Class WPObjectCacheAdapter
 */
class WpObjectCacheAdapter extends AbstractAdapter {

    const CACHE_GROUP = 'icds';

    /**
     * WPObjectCacheAdapter constructor.
     *
     * @param string $namespace
     * @param int $defaultLifetime
     */
    public function __construct( string $namespace = '', int $defaultLifetime = 0 ) {
        parent::__construct( $namespace, $defaultLifetime );
    }

    /**
     * @param array $ids
     *
     * @return iterable
     */
    protected function doFetch( array $ids ): iterable {
        foreach ( $ids as $id ) {
            $result = wp_cache_get( $id, self::CACHE_GROUP );

            if ( $result !== false ) {
                yield $result;
            }
        }
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    protected function doHave( string $id ): bool {
        return wp_cache_get( $id, self::CACHE_GROUP ) !== false;
    }

    /**
     * @param string $namespace
     *
     * @return bool
     */
    protected function doClear( string $namespace ): bool {
        if ( function_exists( 'wp_cache_delete_group' ) ) {
            return wp_cache_delete_group( self::CACHE_GROUP );
        }

        return wp_cache_flush();
    }

    /**
     * @param array $ids
     *
     * @return bool
     */
    protected function doDelete( array $ids ): bool {
        $result = [];

        foreach ( $ids as $id ) {
            $result[] = wp_cache_delete( $id );
        }

        return count( array_filter( $result ) ) > 0;
    }

    /**
     * Persists several cache items immediately.
     *
     * @param array $values The values to cache, indexed by their cache identifier
     * @param int $lifetime The lifetime of the cached values, 0 for persisting until manual cleaning
     *
     * @return array|bool The identifiers that failed to be cached or a boolean stating if caching succeeded or not
     */
    protected function doSave( array $values, int $lifetime ): bool|array {
        $failedToSave = [];

        foreach ( $values as $key => $value ) {
            if ( false === wp_cache_set( $key, $value, self::CACHE_GROUP, $lifetime ) ) {
                $failedToSave[] = $key;
            }
        }

        if ( !empty( $failedToSave ) ) {
            return $failedToSave;
        }

        return true;
    }
}

