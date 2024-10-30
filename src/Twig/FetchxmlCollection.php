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

namespace AlexaCRM\Nextgen\Twig;

use AlexaCRM\Nextgen\Entity;
use AlexaCRM\Nextgen\TwigStylePropertiesTrait;
use AlexaCRM\Xrm\EntityCollection;

/**
 * Represents a collection of records resulting from a FetchXML query.
 */
class FetchxmlCollection {

    use TwigStylePropertiesTrait;

    /**
     * @var Entity[]
     */
    public array $entities = [];

    public ?int $totalRecordCount = null;

    public ?bool $moreRecords = null;

    public ?string $pagingCookie = null;

    /**
     * @param EntityCollection $collection
     *
     * @return FetchxmlCollection
     */
    public static function createFromEntityCollection( EntityCollection $collection ): FetchxmlCollection {
        $fc = new FetchxmlCollection();
        $fauxRecord = new FauxRecord();
        try {
            foreach ( $collection->Entities as $entity ) {
                $fc->entities[] = $fauxRecord->createFromEntity( $entity );
            }
        }
        catch ( \Error|\Exception $e ) {
            DebugExceptionTrap::catchEx( $e );
            return new FetchxmlCollection();
        }

        $fc->totalRecordCount = $collection->TotalRecordCount;
        $fc->moreRecords = $collection->MoreRecords;
        $fc->pagingCookie = $collection->PagingCookie;

        return $fc;
    }
}
