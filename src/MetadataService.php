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

use AlexaCRM\Cache\NullCacheItem;
use AlexaCRM\StrongSerializer\Reference;
use AlexaCRM\WebAPI\MetadataRegistry;
use AlexaCRM\Xrm\Metadata\EntityMetadata;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * Provides access to metadata of the configured Dataverse instance.
 */
class MetadataService {

    use SingletonTrait;

    protected ?MetadataRegistry $registry = null;

    protected ?AdapterInterface $storage = null;

    /**
     * Provides the metadata registry.
     *
     * @return MetadataRegistry
     */
    public function getRegistry(): ?MetadataRegistry {
        if ( $this->registry instanceof MetadataRegistry ) {
            return $this->registry;
        }

        if ( !ConnectionService::instance()->isAvailable() ) {
            return null;
        }

        $client = ConnectionService::instance()->getClient();
        $this->registry = ( new PluginMetadataRegistry( $client ) )->withStorage( $this->getStorage() );

        return $this->registry;
    }

    /**
     * Return list of all entities as array
     *
     * @return array List of entities [ 'entityLogicalName' => 'entityDisplayName' ]
     */
    public function getEntitiesList(): ?array {
        $cachePool = $this->getStorage();
        $logger = LoggerProvider::instance()->getLogger();

        try {
            $cachedList = $cachePool->getItem( 'EntityDefinitions' );
        } catch ( InvalidArgumentException $e ) {
            $cachedList = new NullCacheItem( 'EntityDefinitions' );
            $logger->warning( 'Cache item is expected but not found.', [
                'key' => 'EntityDefinitions',
            ] );
        }

        if ( $cachedList->isHit() ) {
            $logger->debug( "[isHit]: Get EntityDefinitions from the cache" );
            return $cachedList->get();
        }
        $logger->debug( "[isHit]: EntityDefinitions was not found in cache" );

        if ( !ConnectionService::instance()->isAvailable() ) {
            return [];
        }

        $odata = ConnectionService::instance()->getClient()->getClient();
        try {
            $queryOptions = [
                'Select' => [ 'LogicalName', 'DisplayName' ],
            ];
            $entitiesDefinitions = $odata->getList( 'EntityDefinitions', $queryOptions );
        } catch ( \AlexaCRM\WebAPI\OData\Exception $e ) {
            return null;
        }

        $deserializer = $this->getRegistry()->newDeserializer();
        $entitiesList = [];


        $ref = new Reference( EntityMetadata::class ); // Use the reference repeatedly in the loop.
        foreach ( $entitiesDefinitions->List as $object ) {
            /** @var EntityMetadata $entityMetadata */
            $entityMetadata = $deserializer->deserialize( $object, $ref );
            $entitiesList[ $entityMetadata->LogicalName ] = $entityMetadata->DisplayName->UserLocalizedLabel->Label ?? $entityMetadata->LogicalName;
        }

        $cachedList->set( $entitiesList );
        $cachePool->save( $cachedList );

        return $entitiesList;
    }

    /**
     * Return list of filtered entities as array
     *
     * @param null $extraEntity
     * @param bool $isDropdown
     *
     * @return array List of entities [ 'entityLogicalName' => 'entityDisplayName' ]
     */
    public function getFilteredEntitiesList( $extraEntity = null, bool $isDropdown = false ): ?array {
        $cachePool = $this->getStorage();
        $filterType = EntityFilter::getFilterType();
        $logger = LoggerProvider::instance()->getLogger();

        $cacheItemKey = "EntityDefinitions_{$filterType}_{$isDropdown}";

        try {
            $cachedList = $cachePool->getItem( $cacheItemKey );
        } catch ( InvalidArgumentException $e ) {
            $cachedList = new NullCacheItem( $cacheItemKey );
            $logger->warning( 'Cache item is expected but not found.', [
                'key' => $cacheItemKey,
            ] );
        }

        if ( $cachedList->isHit() ) {
            $logger->debug( "[isHit]: Get $cacheItemKey from the cache" );
            return EntityFilter::addExtraEntity( $extraEntity, $cachedList->get() );
        }
        $logger->debug( "[isHit]: $cacheItemKey was not found in cache" );

        if ( !ConnectionService::instance()->isAvailable() ) {
            return [];
        }

        $odata = ConnectionService::instance()->getClient()->getClient();
        try {
            $queryOptions = [
                'Select' => [ 'LogicalName', 'DisplayName', 'IsCustomEntity', 'IsManaged' ],
            ];
            $entitiesDefinitions = $odata->getList( 'EntityDefinitions', $queryOptions );
        } catch ( \AlexaCRM\WebAPI\OData\Exception $e ) {
            return null;
        }

        $deserializer = $this->getRegistry()->newDeserializer();
        $ref = new Reference( EntityMetadata::class ); // Use the reference repeatedly in the loop.
        $filteredEntities = EntityFilter::getFilteredEntities( $entitiesDefinitions->List, $ref, $deserializer, $extraEntity, $isDropdown );
        asort($filteredEntities);
        $cachedList->set( $filteredEntities );
        $cachePool->save( $cachedList );

        return $filteredEntities;
    }

    /**
     * Provides a storage for metadata.
     *
     * @return AdapterInterface
     */
    protected function getStorage(): AdapterInterface {
        if ( $this->storage === null ) {
            $this->storage = CacheProvider::instance()->providePool( 'metadata', WEEK_IN_SECONDS );
        }

        return $this->storage;
    }

}
