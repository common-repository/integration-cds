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

namespace AlexaCRM\Nextgen;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\Cache\SpreadExpirationFilesystemAdapter;
use AlexaCRM\Nextgen\Cache\WpObjectCacheAdapter;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * Provides cache facilities for various needs.
 */
class CacheProvider {

    use SingletonTrait;

    /**
     * Auto discovery for suitable cache storage.
     */
    const FORCE_AUTO = 'auto';

    /**
     * Force files storage.
     */
    const FORCE_FILES = 'files';

    /**
     * Force WP Object Cache storage.
     */
    const FORCE_WPCACHE = 'wpcache';

    /**
     * Force disable cache.
     */
    const FORCE_OFF = 'off';

    /**
     * The directory in the WordPress uploads folder.
     */
    const STORAGE_DIR = 'integration-cds';

    /**
     * Keys used to store option in the WP database.
     */
    const OPTION_KEY = 'icds_cachePools';

    /**
     * Default internal pool name.
     */
    const DEFAULT_POOL = '__default';

    protected ?string $storagePath = null;

    /**
     * @var AdapterInterface[]
     */
    protected array $cachePools = [];

    /**
     * @var string
     */
    protected string $cacheAdapter = 'N/A';

    /**
     * Provides a cache pool with an optional namespace.
     *
     * Namespace `__default` is reserved for the default (empty) namespace.
     *
     * @param string $namespace
     * @param float|int $ttl
     *
     * @return AdapterInterface
     */
    public function providePool( string $namespace = '', float|int $ttl = DAY_IN_SECONDS ): AdapterInterface {
        $icdsDisableCache = AdvancedSettingsProvider::instance( 'ICDS_DISABLE_CACHE' );

        if ( $icdsDisableCache->isTrue() ) {
            return new NullAdapter();
        }

        $forced = static::FORCE_AUTO;
        $icdsCacheStorage = AdvancedSettingsProvider::instance( 'ICDS_CACHE_STORAGE' );
        if ( $icdsCacheStorage->isSet() ) {
            if ( strtolower( $icdsCacheStorage->getValue() ) === static::FORCE_FILES ) {
                $forced = static::FORCE_FILES;
            } elseif ( strtolower( $icdsCacheStorage->getValue() ) === static::FORCE_WPCACHE ) {
                $forced = static::FORCE_WPCACHE;
            } elseif ( strtolower( $icdsCacheStorage->getValue() ) === static::FORCE_OFF ) {
                $forced = static::FORCE_OFF;
            }
        }

        if ( $forced === static::FORCE_OFF ) {
            return new NullAdapter();
        }

        $key = $namespace;
        if ( $namespace === '' ) {
            $key = self::DEFAULT_POOL;
        }

        if ( WP_DEBUG && !in_array( $key, $this->getPoolNames() ) ) {
            LoggerProvider::instance()->getLogger()->warning( __( 'Using unregistered cache pool', 'integration-cds' ), [ 'pool' => $key ] );
        }

        $poolKey = $key . $ttl;

        if ( array_key_exists( $poolKey, $this->cachePools ) ) {
            return $this->cachePools[ $poolKey ];
        }

        if ( wp_using_ext_object_cache() === true && ( $forced !== static::FORCE_FILES ) ) {
            $pool = new WpObjectCacheAdapter( $key, $ttl );
            $this->cachePools[ $poolKey ] = $pool;
            $this->cacheAdapter = 'Wordpress Object Cache';

            return $pool;
        }

        if ( !StorageHelper::isStorageAvailable() ) {
            $pool = new NullAdapter();
            $this->cachePools[ $poolKey ] = $pool;

            return $pool;
        }

        if ( $forced !== static::FORCE_WPCACHE ) {
            $pool = new FilesystemAdapter( $key, $ttl, StorageHelper::getStoragePath() );
            $this->cacheAdapter = 'Files';

            $this->cachePools[ $poolKey ] = $pool;

            $usedPools = get_option( static::OPTION_KEY, [] );
            if ( !in_array( $key, $usedPools ) ){
                $usedPools[] = $key;
                update_option( static::OPTION_KEY, $usedPools );
            }

            return $pool;
        }

        return new NullAdapter();
    }

    /**
     * Returns a list of cache pools specified by a text pattern.
     *
     * @param string $pattern Regexp pattern to match pools by name.
     *
     * @return AdapterInterface[]
     */
    public function getPools( string $pattern ): array {
        $usedPools = get_option( static::OPTION_KEY, [] );

        $matched = preg_grep( $pattern, $usedPools );

        $result = [];
        foreach ( $matched as $poolName ) {
            $result[ $poolName ] = $this->providePool( $poolName );
        }

        return $result;
    }

    /**
     * Returns a list of registered cache pool names.
     *
     * @return string[]
     */
    public function getPoolNames(): array {
        /**
         * Filters the registered cache pools.
         *
         * @param string[] $pools
         */
        return apply_filters( 'integration-cds/cache/pools', [
            self::DEFAULT_POOL,
            'webapi',
            'metadata',
            'data',
            'fetchxml',
            'entityrecords',
        ] );
    }

    /**
     * Returns name of used cache provider.
     *
     * @return string
     */
    public function getCacheAdapter(): string {
        return $this->cacheAdapter;
    }

    /**
     * Clears specified registered cache pool or all registered pools if not specified.
     *
     * @param string|string[]|null $poolName
     */
    public function clear( array|string $poolName = null ): void {
        if ( is_array( $poolName ) ) {
            foreach ( $poolName as $pool ) {
                $this->providePool( $pool )->clear();
            }

            return;
        }

        if ( is_string( $poolName ) ) {
            $this->providePool( $poolName )->clear();

            return;
        }

        $poolNames = $this->getPoolNames();
        foreach ( $poolNames as $poolName ) {
            $this->providePool( $poolName )->clear();
        }
    }

    /**
     * Provisions a storage path is created and returns one.
     *
     * @return string|null Null if the storage path is not available.
     */
    protected function getStoragePath(): ?string {
        static $unavailable = false;

        if ( $unavailable ) {
            return null;
        }

        if ( $this->storagePath !== null ) {
            return $this->storagePath;
        }

        $logger = LoggerProvider::instance()->getLogger();

        $uploadDir = wp_upload_dir();
        if ( $uploadDir['error'] !== false ) {
            $logger->error( __( 'Could not retrieve the storage path: ', 'integration-cds' ) . $uploadDir['error'] );
            $unavailable = true;

            return null;
        }

        $storagePath = $uploadDir['basedir'] . '/' . static::STORAGE_DIR;
        $success = wp_mkdir_p( $storagePath );
        if ( !$success ) {
            $logger->error( __( "Could not create the storage path", 'integration-cds' ) . "`{$storagePath}`" );
            $unavailable = true;

            return null;
        }

        $this->storagePath = $storagePath;

        return $storagePath;
    }

}
