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

use AlexaCRM\Xrm\Metadata\FileAttributeMetadata;
use AlexaCRM\Xrm\Metadata\ImageAttributeMetadata;
use AlexaCRM\Xrm\Metadata\StringAttributeMetadata;

/**
 * Dataverse file-related utilities.
 */
class DataverseFileUtil {

    /**
     * Tells whether the column in the given table is file-like,
     * i.e. is a File or Image column, or annotation[documentbody].
     *
     * @param string $logicalName
     * @param string $column
     *
     * @return bool
     */
    public static function isFilelikeColumn( string $logicalName, string $column ): bool {
        if ( $logicalName === 'annotation' && $column === 'documentbody' ) {
            return true;
        }

        $md = MetadataService::instance()->getRegistry()->getDefinition( $logicalName );
        if ( $md === null ) {
            return false;
        }

        $attrMd = $md->Attributes[ $column ] ?? null;
        if ( $attrMd instanceof FileAttributeMetadata || $attrMd instanceof ImageAttributeMetadata ) {
            return true;
        }

        /*
         * Due to the quirk, to verify the File attribute, we need to check the _name attribute (String).
         * We additionally check AttributeOf of the name attribute to restrict access to other legit attributes
         * that end with _name, e.g. address1_name.
         */

        $nameField = $column . '_name';
        if ( !isset( $md->Attributes[ $nameField ] ) ) {
            return false;
        }

        $nameMd = $md->Attributes[ $nameField ];

        return $nameMd instanceof StringAttributeMetadata && $nameMd->AttributeOf === $column;
    }

}
