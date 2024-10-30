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

use AlexaCRM\WebAPI\MetadataRegistry;
use AlexaCRM\Xrm\Metadata\DateTimeAttributeMetadata;
use AlexaCRM\Xrm\Metadata\DateTimeBehavior;
use AlexaCRM\Xrm\Metadata\EntityMetadata;

/**
 * Provides access to Dynamics 365 organization metadata.
 */
class PluginMetadataRegistry extends MetadataRegistry {

    public function getDefinition( string $logicalName ): ?EntityMetadata {
        $entityMetadata = parent::getDefinition( $logicalName );
        $dateTimeAddons = [
            '_local' => '(Local DateTime)',
            '_local_date' => '(Local Date)',
            '_local_time' => '(Local Time)',
        ];

        foreach ( $entityMetadata->Attributes as $attribute => $attributeMetadata ) {
            if ( $attributeMetadata instanceof DateTimeAttributeMetadata && $attributeMetadata->DateTimeBehavior->Value === DateTimeBehavior::UserLocal ) {
                $label = $attributeMetadata->DisplayName->UserLocalizedLabel->Label;

                foreach ( $dateTimeAddons as $addon => $labelName ) {
                    $attributeMetadataDate = clone $attributeMetadata;
                    $displayName = clone $attributeMetadata->DisplayName;
                    $userLocalizedLabel = clone $attributeMetadata->DisplayName->UserLocalizedLabel;

                    $attributeName = $attribute . $addon;
                    $userLocalizedLabel->Label = $label . $labelName;
                    $attributeMetadataDate->LogicalName = $attributeName;
                    $displayName->UserLocalizedLabel = $userLocalizedLabel;
                    $attributeMetadataDate->DisplayName = $displayName;

                    $entityMetadata->Attributes[ $attributeName ] = $attributeMetadataDate;
                }
            }
        }

        return $entityMetadata;
    }
}
