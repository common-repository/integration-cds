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

use AlexaCRM\WebAPI\Client;
use AlexaCRM\WebAPI\OData\AuthenticationException;
use AlexaCRM\WebAPI\OData\EntityNotSupportedException;
use AlexaCRM\WebAPI\OData\ODataException;
use AlexaCRM\WebAPI\OData\TransportException;
use AlexaCRM\WebAPI\OrganizationException;
use AlexaCRM\WebAPI\SerializationHelper;
use AlexaCRM\WebAPI\ToolkitException;
use AlexaCRM\Xrm\ColumnSet;
use AlexaCRM\Xrm\Entity as XrmEntity;
use AlexaCRM\Xrm\EntityCollection as XrmEntityCollection;
use AlexaCRM\Xrm\EntityReference;
use AlexaCRM\Xrm\Metadata\FileAttributeMetadata;
use AlexaCRM\Xrm\Metadata\ImageAttributeMetadata;
use AlexaCRM\Xrm\Metadata\StringAttributeMetadata;
use AlexaCRM\Xrm\Query\FetchExpression;
use AlexaCRM\Xrm\Query\QueryBase;
use AlexaCRM\Xrm\Query\QueryByAttribute;

/**
 * Provides more options to retrieve data from Dataverse compared to the original Web API Client.
 */
class WebApiClient extends Client {

    /**
     * Creates a record.
     *
     * @param XrmEntity $entity
     *
     * @return string ID of the new record.
     * @throws AuthenticationException
     * @throws OrganizationException
     * @throws ToolkitException
     */
    public function Create( XrmEntity $entity ): string {
        return $this->upsertWithFiles( $entity, function( XrmEntity $entity ) {
            return parent::Create( $entity );
        } );
    }

    /**
     * Updates an existing record.
     *
     * @param Entity $entity
     *
     * @return void
     * @throws AuthenticationException
     * @throws EntityNotSupportedException
     * @throws ODataException
     * @throws OrganizationException
     * @throws ToolkitException
     * @throws TransportException
     */
    public function Update( XrmEntity $entity ): void {
        $this->upsertWithFiles( $entity, function( XrmEntity $entity ) {
            parent::Update( $entity );

            return $entity->Id;
        } );
    }

    /**
     * Retrieves a collection of records.
     *
     * @param QueryBase $query A query that determines the set of records to retrieve.
     *
     * @return EntityCollection
     * @throws AuthenticationException
     * @throws OrganizationException
     * @throws ToolkitException
     */
    public function RetrieveMultiple( QueryBase $query ): EntityCollection {

        $collection = new EntityCollection();
        $retrieveResult = null;

        if ( $query instanceof FetchExpression ) {
            $retrieveResult = $this->retrieveViaFetchXML( $query );
        }

        if ( $query instanceof QueryByAttribute ) {
            $retrieveResult = $this->retrieveViaQueryByAttribute( $query );
        }

        if ( !$retrieveResult instanceof XrmEntityCollection ) {
            return new EntityCollection();
        }

        $collection->PagingCookie = $retrieveResult->PagingCookie;
        $collection->MoreRecords = $retrieveResult->MoreRecords;
        $collection->TotalRecordCount = $retrieveResult->TotalRecordCount;
        $collection->TotalRecordCountLimitExceeded = $retrieveResult->TotalRecordCountLimitExceeded;
        $collection->EntityName = $retrieveResult->EntityName;

        $entities = $retrieveResult->Entities;
        $retrieveResult->Entities = [];
        $nextgenEntity = new Entity();
        foreach ( $entities as $entity ) {
            $collection->Entities[] = $nextgenEntity->createFromEntity( $entity );
        }

        return $collection;
    }

    /**
     * Retrieves a record.
     *
     * @param string $entityName
     * @param string $entityId Record ID.
     * @param ColumnSet $columnSet
     *
     * @return Entity|null
     * @throws AuthenticationException
     * @throws OrganizationException
     * @throws ToolkitException
     */
    public function RetrieveOne( string $entityName, string $entityId, ColumnSet $columnSet ): ?Entity {
        $entity = $this->Retrieve( $entityName, $entityId, $columnSet );

        if ( $entity ) {
            $nextgenEntity = new Entity();

            return $nextgenEntity->createFromEntity( $entity );
        }

        return null;
    }

    /**
     * Retrieves a record by EntityReference.
     *
     * Supports referencing by GUID and alternate keys.
     *
     * @param EntityReference $ref
     * @param ColumnSet $columnSet
     *
     * @return Entity|null
     * @throws OrganizationException
     * @throws ToolkitException
     * @throws AuthenticationException
     */
    public function RetrieveByReference( EntityReference $ref, ColumnSet $columnSet ): ?Entity {
        if ( isset( $ref->Id ) ) {
            $entity = $this->Retrieve( $ref->LogicalName, $ref->Id, $columnSet );

            if ( $entity ) {
                $nextgenEntity = new Entity();

                return $nextgenEntity->createFromEntity( $entity );
            }
        }

        if ( $ref->KeyAttributes === null || count( $ref->KeyAttributes ) === 0 ) {
            throw new \InvalidArgumentException( 'WebApiClient::RetrieveByReference() expects populated EntityReference::KeyAttributes' );
        }

        try {
            $metadata = $this->client->getMetadata();
            $collectionName = $metadata->getEntitySetName( $ref->LogicalName );
            $entityMap = $metadata->getEntityMap( $ref->LogicalName );
            $inboundMap = $entityMap->inboundMap;
            $columnMapping = array_flip( $inboundMap );

            $options = [];
            if ( $columnSet->AllColumns !== true ) {
                $options['Select'] = [];

                // $select must not be empty. Add primary key.
                $options['Select'][] = $entityMap->key;

                foreach ( $columnSet->Columns as $column ) {
                    if ( !array_key_exists( $column, $columnMapping ) ) {
                        $this->getLogger()->warning( __( "No inbound attribute mapping found for {$ref->LogicalName}[{$column}]", 'integration-cds' ) );
                        continue;
                    }

                    $options['Select'][] = $columnMapping[ $column ];
                }
            }

            $req = [];
            foreach ( $ref->KeyAttributes as $attribute => $value ) {
                $altAttr = $attribute;
                $altVal = $value;

                if ( isset( $entityMap->fieldTypes[ $attribute ] ) ) {
                    $attrType = $entityMap->fieldTypes[ $attribute ];

                    /*
                     * Only several types of attributes are allowed as alternate key attributes.
                     * See https://docs.microsoft.com/en-us/powerapps/developer/common-data-service/define-alternate-keys-entity#create-alternate-keys
                     */
                    switch ( true ) {
                        /*
                         * Lookup key attributes must be referenced by their read-only name, e.g. _ownerid_value.
                         * GUID should not be enclosed in quotes.
                         */
                        case ( $attrType === 'Emd.Guid' ):
                            $altAttr = $columnMapping[ $attribute ];
                            break;
                        case ( is_string( $value ) ):
                            $altVal = "'{$value}'";
                            break;
                        case $value === null:
                            $altVal = 'null';
                            break;
                    }
                }

                $req[] = implode( '=', [ $altAttr, $altVal ] );
            }

            $response = $this->client->getRecord( $collectionName, implode( ',', $req ), $options );

            $serializer = new SerializationHelper( $this->client );
            $newRef = clone $ref;
            if ( isset( $response->{$entityMap->key} ) ) {
                $newRef->Id = $response->{$entityMap->key};
            }

            $entity = $serializer->deserializeEntity( $response, $newRef );
            $nextgenEntity = new Entity();

            return $nextgenEntity->createFromEntity( $entity );
        } catch ( ODataException $e ) {
            if ( $e->getCode() === 404 ) {
                return null;
            }

            throw new OrganizationException( __( 'Retrieve request failed: ' . $e->getMessage(), 'integration-cds' ), $e );
        } catch ( TransportException $e ) {
            throw new ToolkitException( $e->getMessage(), $e );
        } catch ( EntityNotSupportedException $e ) {
            throw new ToolkitException( __( "Cannot retrieve: entity `{$ref->LogicalName}` is not supported", 'integration-cds' ), $e );
        }
    }

    /**
     * Creates or updates a record and handles file and image uploads.
     *
     * @param Entity $entity
     * @param callable $cb A callback that received a single Entity parameter and returns a record identifier.
     *
     * @return string GUID that identifies a newly created or updated record.
     * @throws AuthenticationException
     * @throws EntityNotSupportedException
     * @throws ODataException
     * @throws OrganizationException
     * @throws ToolkitException
     * @throws TransportException
     */
    protected function upsertWithFiles( XrmEntity $entity, callable $cb ): string {
        $record = clone $entity;

        $md = MetadataService::instance()->getRegistry()->getDefinition( $record->LogicalName );
        if ( $md === null ) {
            throw new ToolkitException( __( "Cannot create: entity `{$record->LogicalName}` is not supported", 'integration-cds' ) );
        }

        $files = [];
        $filenames = [];
        foreach ( $record->Attributes as $field => $value ) {
            /*
             * Currently (9.1, Jan 2021), File type attributes are not available in metadata.
             * For `filecolumn`, only `filecolumn_name` (String) is exposed.
             * Until further notice, use a heuristic to determine if a file is being sent and pray.
             */

            $nameField = $field . '_name';

            $fileMd = $md->Attributes[ $field ] ?? null;
            $nameMd = $md->Attributes[ $nameField ] ?? null;

            if ( $fileMd instanceof FileAttributeMetadata || $fileMd instanceof ImageAttributeMetadata
                 || ( $nameMd instanceof StringAttributeMetadata && $nameMd->AttributeOf === $field ) ) {
                /*
                 * Unlike Image, File field could not be decoded in CustomFormMode::prepareData()
                 * due to the metadata quirk.
                 */
                if ( $fileMd === null ) {
                    $files[ $field ] = base64_decode( $value );
                } else {
                    $files[ $field ] = $value;
                }

                unset( $record->Attributes[ $field ] );

                /*
                 * File attributes have a companion field that stores the file name.
                 * Image attributes don't have such field, but let's support it just in case.
                 */
                $nameField = $field . '_name';
                if ( isset( $record->Attributes[ $nameField ] ) ) {
                    $filenames[ $field ] = $record->Attributes[ $nameField ];
                    unset( $record->Attributes[ $nameField ] );
                }
            }
        }

        $recordId = $cb( $record );

        if ( count( $files ) === 0 ) {
            return $recordId;
        }

        $odm = $this->client->getMetadata();
        $collectionName = $odm->getEntitySetName( $record->LogicalName );
        $odc = $this->client;
        foreach ( $files as $field => $value ) {
            $odc->upload( $collectionName, $recordId, $field, $value, $filenames[ $field ] ?? null );
        }

        return $recordId;
    }

}
