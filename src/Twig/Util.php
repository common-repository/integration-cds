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

namespace AlexaCRM\Nextgen\Twig;

use AlexaCRM\Nextgen\ConnectionService;
use AlexaCRM\Nextgen\LoggerProvider;
use AlexaCRM\Nextgen\MetadataService;
use AlexaCRM\Xrm\Entity;
use AlexaCRM\Xrm\EntityReference;
use Exception;

/**
 * Provides utilities for Twig-related operations.
 */
class Util {

    /**
     * Converts snake_case string to lowerCamelCase string.
     *
     * @param string $string
     *
     * @return string
     */
    public static function snakeToCamel( string $string ): string {
        $lowerName = strtolower( $string );

        $convertedName = preg_replace_callback( '/_([a-z])/', function( $matches ) {
            return strtoupper( $matches[1] );
        }, $lowerName );

        return $convertedName;
    }

    /**
     * Converts an Entity or an EntityReference-like object/map to a strongly-typed EntityReference.
     *
     * Returns `NULL` if failed to create an EntityReference, i.e. `LogicalName` not found.
     *
     * @param EntityReference|Entity|array|object $value
     *
     * @return EntityReference|null
     */
    public static function toEntityReference( $value ): ?EntityReference {
        if ( $value instanceof EntityReference ) {
            return $value;
        }

        if ( $value instanceof Entity ) {
            $ref = $value->ToEntityReference();

            try {
                if ( ConnectionService::instance()->isAvailable() ) {
                    $primaryNameAttribute = MetadataService::instance()->getRegistry()->getDefinition( $ref->LogicalName )->PrimaryNameAttribute;
                    $ref->Name = $value[ $primaryNameAttribute ];
                }
            } catch( Exception $e ) {}

            return $ref;
        }

        if ( is_object( $value ) ) {
            $value = (array)$value;
        }

        if ( !is_array( $value ) || !array_key_exists( 'LogicalName', $value ) ) {
            return null;
        }

        $ref = new EntityReference( $value['LogicalName'] );
        if ( array_key_exists( 'Id', $value ) ) {
            $ref->Id = $value['Id'];
        }

        if ( array_key_exists( 'Name', $value ) ) {
            $ref->Name = $value['Name'];
        }

        return $ref;
    }

    /**
     * Converts ISO 8601 duration string to seconds.
     *
     * @param string $duration Duration string compatible with ISO 8601 duration specification.
     *
     * @return int Time duration in seconds.
     */
    public static function datetimeDurationToSeconds( string $duration ): int {
        $now = new \DateTimeImmutable();
        try {
            $expired = $now->add( new \DateInterval( $duration ) );
        } catch ( Exception $e ) {
            LoggerProvider::instance()->getLogger()->warning( $e->getMessage() );

            return 0;
        }

        return $expired->getTimestamp() - $now->getTimestamp();
    }

}
