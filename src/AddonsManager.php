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

namespace AlexaCRM\Nextgen;

use Psr\Cache\InvalidArgumentException;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Implements a managed for plugin addons.
 */
class AddonsManager {

    use SingletonTrait;

    /**
     * List of installed addons. Mapped by Addon::$name.
     *
     * @var Addon[]
     */
    protected array $addons = [];

    /**
     * AddonsManager constructor.
     *
     * Initializes the installed addons.
     */
    protected function __construct() {
        /**
         * Filters the collection of plugin addons.
         *
         * @param Addon[] $addons
         */
        $addons = apply_filters( 'integration-cds/addons', [] );

        /** @var Addon[] $addons */
        $addons = array_filter( $addons, fn( $addon ) => $addon instanceof Addon );

        foreach ( $addons as $addon ) {
            $this->addons[ $addon->getName() ] = $addon;
        }

        $installed = $this->getInstalledAddons();
        $notActive = [];
        foreach ( $installed as $addon ) {
            if ( !array_key_exists( $addon->getName(), $this->addons ) ) {
                $notActive[ $addon->getName() ] = $addon;
            }
        }

        $this->addons = array_merge( $this->addons, $notActive );
    }

    /**
     * Returns specified addon.
     *
     * @param string $name
     *
     * @return Addon|null
     */
    public function getAddon( string $name ): ?Addon {
        if ( !$this->isInstalled( $name ) ) {
            return null;
        }

        if ( !$this->isActive( $name ) ) {
            return null;
        }

        return $this->addons[ $name ];
    }

    /**
     * Returns the list of installed and active addons.
     *
     * @return Addon[]
     *
     * @deprecated 2.39 Use AddonsManager::getActiveAddons() instead.
     */
    public function getAddons(): array {
        return $this->getActiveAddons();
    }

    /**
     * Returns the list of installed and active addons.
     *
     * @return Addon[]
     */
    public function getActiveAddons(): array {
        $addons = $this->addons;
        $wpActivePlugins = array_flip(get_option('active_plugins'));

        return array_intersect_key($addons, $wpActivePlugins);
    }

    /**
     * Returns list of addons available for download.
     *
     * @return ManagedAddon[]
     */
    public function getManagedAddons(): array {
        $logger = LoggerProvider::instance()->getLogger();
        $cache = CacheProvider::instance()->providePool( 'addons' );

        try {
            $cachedAddons = $cache->getItem( 'available-addons' );
            if ( $cachedAddons->isHit() ) {
                return $cachedAddons->get();
            }
        } catch ( InvalidArgumentException $e ) {
            $logger->warning( 'Failed retrieving data from cache: ' . $e->getMessage() );
        }

        $httpClient = new \GuzzleHttp\Client();
        $addons = [];

        try {
            $response = $httpClient->get( 'https://wpab.blob.core.windows.net/release/addons.json' )->getBody();
            $addons = json_decode( $response, true );
        } catch ( \Exception $e ) {
            $logger->error( 'Failed retrieving addons list from the server: ' . $e->getMessage(), [
                'error' => $e->getTrace(),
            ] );
        }

        array_walk( $addons, function( &$addon, $name ) use ( $httpClient, $logger ) {
            $addonRaw = $addon;

            if ( !empty( $addonRaw['manifest'] ) ) {
                try {
                    $manifestResponse = $httpClient->get( $addonRaw['manifest'] )->getBody();
                    $manifest = json_decode( $manifestResponse, true );
                    $addon = ManagedAddon::createFromArray( array_merge( $manifest, [ 'name' => $name ] ) );

                    if ( empty( $addon->title ) ) {
                        $addon->title = $addonRaw['title'];
                    }

                    return;
                } catch ( \Exception $e ) {
                    $logger->error( 'Failed retrieving addon data from the server: ' . $e->getMessage(), [
                        'addon' => $addon,
                        'error' => $e,
                    ] );
                }
            }

            $addon = ManagedAddon::createFromArray( array_merge( $addonRaw, [ 'name' => $name ] ) );
        } );

        if ( isset( $cachedAddons ) ) {
            $cachedAddons->set( $addons );
            $cachedAddons->expiresAfter( 1 * DAY_IN_SECONDS );
            $cache->save( $cachedAddons );
        }

        return $addons;
    }

    /**
     * Returns list of installed addons.
     *
     * @return Addon[]
     */
    public function getInstalledAddons(): array {
        $wpPlugins = get_plugins();
        $installed = array_intersect_key( $this->getManagedAddons(), $wpPlugins );

        $result = [];
        foreach ( $installed as $addon ) {
            $addonArray = (array)$addon;
            $addonArray['name'] = $addon->getName();
            $addonArray['status'] = Addon::STATUS_INSTALLED;
            $result[ $addon->getName() ] = Addon::createFromArray( $addonArray );
        }

        return $result;
    }

    /**
     * Determines whether the addon is installed, active or disabled.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isInstalled( string $name ): bool {
        if ( $this->isActive( $name ) ) {
            return true;
        }

        return array_key_exists( $name, $this->getInstalledAddons() );
    }

    /**
     * Determines whether the addon is activated.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isActive( string $name ): bool {
        return array_key_exists( $name, $this->addons );
    }

}
