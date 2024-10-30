<?php
/**
 * Copyright 2022 AlexaCRM
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

use AlexaCRM\Nextgen\ConnectionService;
use AlexaCRM\Nextgen\Entity;
use AlexaCRM\Nextgen\FileProxy;
use AlexaCRM\Nextgen\LoggerProvider;
use AlexaCRM\Nextgen\MetadataService;
use AlexaCRM\Xrm\Metadata\FileAttributeMetadata;
use AlexaCRM\Xrm\Metadata\ImageAttributeMetadata;
use AlexaCRM\Xrm\Query\QueryByAttribute;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Implements a proxy record object for the entity record fetcher for Twig templates.
 */
class FauxRecord extends Entity {

    /**
     * @param $offset
     *
     * @return FauxColumn
     */
    public function offsetGet( $offset ): mixed {
        $attrValue = parent::offsetGet( $offset );

        try {
            $metadata = MetadataService::instance()->getRegistry()->getDefinition( $this->LogicalName );
            $attrMetadata = $metadata->Attributes[ $offset ] ?? null;
        } catch ( \Exception $e ) {
            LoggerProvider::instance()->getLogger()->error( 'Failed fetching an attribute extra data: ' . $e->getMessage(), [
                'attribute' => $offset,
                'attributeValue' => $attrValue,
            ] );

            return $attrValue;
        }

        if ( $attrMetadata instanceof FileAttributeMetadata ) {
            $query = new QueryByAttribute( 'fileattachment' );
            $query->AddAttributeValue( 'fileattachmentid', $attrValue );
            try {
                $fileAttachment = ConnectionService::instance()->getClient()->RetrieveMultiple( $query );
            } catch ( \Exception $e ) {
                return $attrValue;
            }

            $attrVaueExt = new FauxColumn( $attrValue );
            $attrVaueExt->Name = $fileAttachment->Entities[0]['filename'];
            $attrVaueExt->Size = $fileAttachment->Entities[0]['filesizeinbytes'];
            $attrVaueExt->Url = FileProxy::getUrl( $this->LogicalName, $this->Id, $offset );
            $attrVaueExt->Type = $fileAttachment->Entities[0]['mimetype'];

            return $attrVaueExt;
        }

        if ( $attrMetadata instanceof ImageAttributeMetadata ) {
            $url = ConnectionService::instance()->getClient()->getClient()->getSettings()->getEndpointURI();
            $attrVaueExt = new FauxColumn( $attrValue );
            $attrVaueExt->Url = $url . $this[ $offset . '_url' ];

            return $attrVaueExt;
        }

        return $attrValue;
    }
}
