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

namespace AlexaCRM\Nextgen\Twig;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\CacheProvider;
use AlexaCRM\Nextgen\CacheSettings;
use AlexaCRM\Nextgen\ConnectionService;
use AlexaCRM\Nextgen\Entity;
use AlexaCRM\Nextgen\LoggerProvider;
use AlexaCRM\Nextgen\SettingsProvider;
use AlexaCRM\Nextgen\TwigProvider;
use AlexaCRM\WebAPI\OData\AuthenticationException;
use AlexaCRM\WebAPI\OrganizationException;
use AlexaCRM\WebAPI\ToolkitException;
use AlexaCRM\Xrm\ColumnSet;
use Psr\Cache\InvalidArgumentException;

/**
 * Implements entity record fetcher for Twig templates.
 */
class FauxEntity implements \ArrayAccess {

    /**
     * Entity name.
     */
    protected string $entityName;

    /**
     * FauxEntity constructor.
     *
     * @param string $entityName
     */
    public function __construct( string $entityName ) {
        $this->entityName = $entityName;
    }

    /**
     * Retrieves an entity record from the Dataverse.
     *
     * @param string $recordId Entity record ID.
     *
     * @return Entity|null
     * @throws AuthenticationException
     * @throws OrganizationException
     * @throws ToolkitException
     * @throws InvalidArgumentException
     */
    public function __get( string $recordId ): ?Entity {
        $columnSet = new ColumnSet( true );

        /** @var CacheSettings $cacheSettings */
        $cacheSettings = SettingsProvider::instance()->getSettings( 'cache' );
        $durations = array_column( $cacheSettings->entityCacheDurations, 'duration', 'entity' );
        $cacheTtl = !empty( $durations[ $this->entityName ] ) ?
            $durations[ $this->entityName ] :
            $cacheSettings->entityCacheDefault;

        $cache = CacheProvider::instance()->providePool( 'entityrecords-' . $this->entityName, Util::datetimeDurationToSeconds( $cacheTtl ) );

        $cachedRecord = $cache->getItem( $recordId );
        if ( $cachedRecord->isHit() ) {
            return $cachedRecord->get();
        }

        try {
            $record = ConnectionService::instance()->getClient()->Retrieve( $this->entityName, $recordId, $columnSet );
        } catch ( \Exception $e ) {
            LoggerProvider::instance()->getLogger()->error( 'Failed fetching a record in Twig template:' . $e->getMessage(), [
                'recordId' => $recordId,
                'page' => get_the_ID(),
            ] );

            return null;
        }

        if ( $record === null ) {
            TwigProvider::instance()->registerError( 404 );

            return null;
        }

        $entityName = $record->LogicalName;

        /**
         * Filters direct access to the CRM records via entitity vatiable.
         *
         * @param bool $isAuthorized Whether to allow access.
         * @param Entity $record CRM record.
         */
        $isAuthorized = apply_filters( "integration-cds/entity/authorize-access-{$entityName}", true, $record );

        if ( !$isAuthorized ) {
            TwigProvider::instance()->registerError( 403 );

            return null;
        }

        $fauxRecord = new FauxRecord();
        $result = $fauxRecord->createFromEntity( $record );

        $cachedRecord->set( $result );
        $cache->save( $cachedRecord );

        return $result;
    }

    /**
     * @param string $recordId
     *
     * @return bool
     * @see FauxEntity::offsetExists()
     */
    public function __isset( string $recordId ): bool {
        return $this->offsetExists( $recordId );
    }

    /**
     * Dynamic properties must appear to exist to surface in Twig templates,
     * and checking record for existence before fetching it is not cost-efficient,
     * thus the method always returns true if the $recordId is a valid GUID.
     *
     * @param string $recordId Entity record ID.
     *
     * @return boolean
     */
    public function offsetExists( $recordId ): bool {
        $guidRegexp = '/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/i';

        return preg_match( $guidRegexp, $recordId ) === 1;
    }

    /**
     * @param mixed $recordId
     *
     * @return Entity
     * @see FauxEntity::__get()
     */
    #[\ReturnTypeWillChange]
    public function offsetGet( $recordId ) {
        return $this->{$recordId};
    }

    /**
     * Object is read-only.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet( $offset, $value ): void {
    }

    /**
     * Object is read-only.
     *
     * @param mixed $offset
     */
    public function offsetUnset( $offset ): void {
    }
}
