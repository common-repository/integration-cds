<?php
/*
 * Copyright 2020 AlexaCRM
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

use AlexaCRM\Xrm\EntityCollection as XrmEntityCollection;

/**
 * Represents a collection of records resulting from a FetchXML query.
 */
class EntityCollection extends XrmEntityCollection{

    use TwigStylePropertiesTrait;

    /**
     * Collection of entities.
     *
     * @var Entity[]
     */
    public array $Entities = [];

    /**
     * Logical name of the entity.
     */
    public ?string $EntityName = null;

    /**
     * Shows whether there are more records available.
     */
    public ?bool $MoreRecords = null;

    /**
     * Paging information.
     */
    public ?string $PagingCookie = null;

    /**
     * Total number of records.
     */
    public ?int $TotalRecordCount = null;

    /**
     * Whether the results of the query exceeds the total record count.
     */
    public ?bool $TotalRecordCountLimitExceeded = null;

    /**
     * @param XrmEntityCollection $collection
     *
     * @return EntityCollection
     */
    public static function createFromEntityCollection( XrmEntityCollection $collection ): EntityCollection {

        $fc = new EntityCollection();
        $nextgenEntity = new Entity();

        foreach ( $collection->Entities as $entity ) {
            $fc->Entities[] = $nextgenEntity->createFromEntity( $entity );
        }
        $fc->TotalRecordCount = $collection->TotalRecordCount;
        $fc->MoreRecords = $collection->MoreRecords;
        $fc->PagingCookie = $collection->PagingCookie;

        return $fc;
    }
}
