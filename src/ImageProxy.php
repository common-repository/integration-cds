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

use AlexaCRM\Xrm\EntityReference;
use finfo;

/**
 * Provides access to image stored in Dataverse.
 */
class ImageProxy {

    public function serve( EntityReference $ref, string $column, bool $isThumb = false, $headers = [] ): int {
        $webapi = ConnectionService::instance()->getClient();
        if ( $webapi === null ) {
            return 500;
        }

        // Allow only file and image attributes, and annotation[documentbody].
        if ( !DataverseFileUtil::isFilelikeColumn( $ref->LogicalName, $column ) ) {
            return 400;
        }

        // TODO: Implement in the toolkit.
        // TODO: Implement chunked downloads.

        $odata = $webapi->getClient();
        $guzzle = $odata->getHttpClient();
        $url = $odata->getSettings()->getEndpointURI();

        $md = $odata->getMetadata();
        $setName = $md->getEntitySetName( $ref->LogicalName );

        $entityMd = MetadataService::instance()->getRegistry()->getDefinition( $ref->LogicalName );
        $isPrimaryImage = $entityMd->PrimaryImageAttribute === $column;

        $fileName = "$column.jpg";
        $sizeStr = $isThumb ? '' : 'size=full';

        if ( $isPrimaryImage ) {
            $imageUrl = $url . sprintf( '%s(%s)/%s?x-ms-file-name=%s', $setName, $ref->Id, $column, $fileName );
        } else {
            $imageUrl = $url . sprintf( '%s(%s)/%s/$value?%s', $setName, $ref->Id, $column, $sizeStr );
        }

        $resp = $guzzle->get( $imageUrl );
        $img = $resp->getBody()->getContents();

        if ( $ref->LogicalName === 'annotation' && $column === 'documentbody' ) {
            $img = base64_decode( $img );
        }

        if ( $isPrimaryImage ) {
            $img = ( json_decode( $img ) )->value;
            $img = base64_decode( $img );
        }

        if ( !isset( $headers['Content-Type'] ) ) {
            $finfo = new finfo( FILEINFO_MIME_TYPE );
            $mime = $finfo->buffer( $img );
            $headers['Content-Type'] = $mime;
        }

        foreach ( $headers as $headerKey => $headerValue ) {
            header( "{$headerKey}: {$headerValue}" );
        }

        echo $img;

        return 200;
    }

}
