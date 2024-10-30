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

use AlexaCRM\Xrm\ColumnSet;
use AlexaCRM\Xrm\EntityReference;

/**
 * Provides download access to file-like objects stored in Dataverse.
 */
class FileProxy {

    public function serve( EntityReference $ref, string $column, $headers = [] ): int {
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

        $resp = $guzzle->get( $url . sprintf( '%s(%s)/%s/$value?size=full', $setName, $ref->Id, $column ) );
        $file = $resp->getBody()->getContents();

        $headers['Content-Type'] ??= 'application/octet-stream';
        $headers['Content-Description'] ??= 'File Transfer';

        if ( !isset( $headers['Content-Disposition'] ) ) {
            $dispositionHeader = $resp->getHeaderLine( 'Content-Disposition' );
            $dispositionHeader = str_replace( 'inline;', 'attachment;', $dispositionHeader );
            $headers['Content-Disposition'] = $dispositionHeader;
        }

        foreach ( $headers as $headerKey => $headerValue ) {
            header( "{$headerKey}: {$headerValue}" );
        }

        echo $file;

        return 200;
    }

    public function serveAnnotation( string $id, $headers = [] ): int {
        $webapi = ConnectionService::instance()->getClient();
        if ( $webapi === null ) {
            return 500;
        }

        $annotation = $webapi->Retrieve( 'annotation', $id, new ColumnSet( [
            'documentbody',
            'filename',
            'isdocument',
        ] ) );

        if ( $annotation === null ) {
            return 404;
        }

        if ( !$annotation['isdocument'] || trim( $annotation['filename'] ) === '' ) {
            return 400;
        }

        $headers['Content-Type'] ??= 'application/octet-stream';
        $headers['Content-Description'] ??= 'File Transfer';
        $headers['Content-Disposition'] ??= 'attachment; filename=' . $annotation['filename'];

        foreach ( $headers as $headerKey => $headerValue ) {
            header( "{$headerKey}: {$headerValue}" );
        }

        echo base64_decode( $annotation['documentbody'] );

        return 200;
    }

    /**
     * Construct a valid url to a file in specified Entity.
     *
     * @param $entityName
     * @param $recordId
     * @param $column
     *
     * @return string|null
     */
    public static function getUrl( $entityName, $recordId, $column ): ?string {
        $webapi = ConnectionService::instance()->getClient();
        if ( $webapi === null ) {
            return null;
        }

        $odata = $webapi->getClient();
        $url = $odata->getSettings()->getEndpointURI();

        try {
            $md = $odata->getMetadata();
            $setName = $md->getEntitySetName( $entityName );
        } catch ( \Exception $e ) {
            LoggerProvider::instance()->getLogger()->error( 'Failed to retrieve a url for the given File column: ' . $e->getMessage(), [
                'entity' => $entityName,
                'recordId' => $recordId,
                'column' => $column,
            ] );

            return null;
        }

        return $url . sprintf( '%s(%s)/%s/$value', $setName, $recordId, $column );
    }

}
