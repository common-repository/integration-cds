<?php
/*
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

namespace AlexaCRM\Nextgen;

use AlexaCRM\WebAPI\OData\Token;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

/**
 * Provides an encrypted cache pool. Used for the toolkit authentication middleware.
 */
class EncryptedDbCachePool extends AbstractAdapter {

    /**
     * WordPress option name for the cache pool.
     *
     * @var string
     */
    protected string $dbKey;

    /**
     * @param string $dbKey
     */
    public function __construct( string $dbKey = 'cache_pool' ) {
        parent::__construct();
        $this->dbKey = $dbKey;
    }

    /**
     * Fetches several cache items.
     *
     * @param array $ids The cache identifiers to fetch
     *
     * @return iterable The corresponding values found in the cache
     */
    protected function doFetch( array $ids ): iterable {
        $items = $this->_getItems();

        $result = [];
        foreach ( $ids as $id ) {
            if ( !array_key_exists( $id, $items ) ) {
                continue;
            }

            // TODO: Implement key expiration.

            $result[ $id ] = $items[ $id ];
        }

        return $result;
    }

    /**
     * Confirms if the cache contains specified cache item.
     *
     * @param string $id The identifier for which to check existence
     *
     * @return bool True if item exists in the cache, false otherwise
     */
    protected function doHave( string $id ): bool {
        $items = $this->_getItems();

        return array_key_exists( $id, $items );
    }

    /**
     * Deletes all items in the pool.
     *
     * @param string $namespace The prefix used for all identifiers managed by this pool
     *
     * @return bool True if the pool was successfully cleared, false otherwise
     */
    protected function doClear( string $namespace ): bool {
        $this->_commitItems( [] );

        return true;
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param array $ids An array of identifiers that should be removed from the pool
     *
     * @return bool True if the items were successfully removed, false otherwise
     */
    protected function doDelete( array $ids ): bool {
        $items = $this->_getItems();
        $items = array_filter( $items, function( $key ) use ( $ids ) {
            return !in_array( $key, $ids, true );
        } );
        $this->_commitItems( $items );

        return true;
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
        // TODO: Implement key expiration.
        $items = $this->_getItems();
        $items = array_merge( $items, $values );
        $this->_commitItems( $items );

        return true;
    }

    /**
     * Retrieves the cache pool items from the storage.
     */
    protected function _getItems(): array {
        $items = '';
        $encrypted = get_option( $this->dbKey, '' );
        $serialized = EncryptionService::instance()->decrypt( $encrypted );
        if ( $serialized ) {
            $items = unserialize( $serialized, [
                'allowed_classes' => [
                    Token::class,
                ],
            ] );
        }
        if ( !is_array( $items ) ) {
            return [];
        }

        return $items;
    }

    /**
     * Commits items to the database.
     *
     * @param array $items
     */
    protected function _commitItems( array $items ): void {
        $serialized = serialize( $items );
        $encrypted = EncryptionService::instance()->encrypt( $serialized );
        update_option( $this->dbKey, $encrypted, true );
    }

}
