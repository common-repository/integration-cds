<?php
/**
 * Copyright 2024 AlexaCRM
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

use AlexaCRM\Nextgen\Twig\User;
use AlexaCRM\WebAPI\OData\AuthenticationException;
use AlexaCRM\WebAPI\OrganizationException;
use AlexaCRM\WebAPI\ToolkitException;
use AlexaCRM\Xrm\Entity as XrmEntity;
use AlexaCRM\Xrm\Metadata\DateTimeAttributeMetadata;
use AlexaCRM\Xrm\Metadata\DateTimeBehavior;
use AlexaCRM\Xrm\Metadata\DateTimeFormat;
use AlexaCRM\Xrm\Metadata\EntityMetadata;
use DateTimeInterface;
use DateTimeZone;

/**
 * Class Entity
 * Represents a record in Dynamics 365.
 *
 * @package AlexaCRM\Nextgen
 */
class Entity extends XrmEntity {

    public ?EntityMetadata $metadata = null;

    public ?XrmEntity $updatedEntity = null;

    public function __get( string $name ) {

        $attributeName = $this->getAttributeName( $name );
        if ( !$this->metadata ) {
            return $this->Attributes[ $attributeName ];
        }

        $attrMetaData = $this->metadata->Attributes[ $attributeName ] ?? null;
        if ( !$attrMetaData instanceof DateTimeAttributeMetadata || $attrMetaData->DateTimeBehavior->Value !== DateTimeBehavior::UserLocal ) {
            return $this->Attributes[ $attributeName ] . 'Z';
        }

        try {
            $dateValue = new \DateTimeImmutable( $this->Attributes[ $attributeName ] );
        } catch ( \Exception ) {
            return null;
        }
        $timeZoneOffset = timezone_offset_get( $this->getUserTimeZone(), $dateValue );
        if ( is_numeric( $timeZoneOffset ) ) {
            $dateValue = $dateValue->setTimestamp( $dateValue->getTimestamp() + (int)$timeZoneOffset );
        }
        if ( str_contains( $name, '_local_time' ) ) {
            return $dateValue->format( 'H:i:s' );
        }
        if ( str_contains( $name, '_local_date' ) ) {
            return $dateValue->format( 'Y-m-d' );
        }

        return $dateValue->format( DateTimeInterface::ATOM );
    }

    /**
     * @throws \Exception
     */
    public function __set( string $name, $value ): void {

        if ( !$this->metadata ) {
            return;
        }
        $attributeName = $this->getAttributeName( $name );
        $attrMetaData = $this->metadata->Attributes[ $attributeName ] ?? null;

        if ( !$attrMetaData instanceof DateTimeAttributeMetadata || !$value ) {
            return;
        }

        $dateValue = new \DateTimeImmutable( $value, new DateTimeZone( 'UTC' ) );
        if ( $attrMetaData->DateTimeBehavior->Value === DateTimeBehavior::UserLocal ) {
            if ( $attrMetaData->Format->getValue() === DateTimeFormat::DateOnly && $this->updatedEntity ) {
                $timeDiff = $updatedEntity->Attributes [ $attributeName . '_local_time' ] ?? '00:00:00';
                $dateValue = new \DateTimeImmutable( $dateValue->format( 'Y-m-d' ) . 'T' . $timeDiff . 'Z' );
            }
            $timeZoneOffset = timezone_offset_get( $this->getUserTimeZone(), $dateValue );
            $dateValue = $dateValue->setTimestamp( $dateValue->getTimestamp() - $timeZoneOffset );
        }

        if ( $attrMetaData->DateTimeBehavior->Value === DateTimeBehavior::DateOnly ) {
            $this->SetAttributeValue( $attributeName, $dateValue->format( 'Y-m-d' ) );
        } else {
            if ( $attrMetaData->DateTimeBehavior->Value === DateTimeBehavior::TimeZoneIndependent || str_contains( $name, '_time' ) || str_contains( $name, '_local_date' ) ) {
                $dateValue = $this->getCorrectDateTime( $name, $dateValue );
            }
            $this->SetAttributeValue( $attributeName, $dateValue->format( 'Y-m-d\TH:i:s' ) . 'Z' );
        }
    }

    /**
     * @param XrmEntity $record
     * @param XrmEntity|null $updatedEntity
     *
     * @return Entity
     * @throws AuthenticationException
     * @throws OrganizationException
     * @throws ToolkitException
     */
    public function toEntity( XrmEntity $record, ?XrmEntity $updatedEntity = null ): XrmEntity {

        $this->Attributes = $record->Attributes;
        $this->updatedEntity = $updatedEntity;

        $this->metadata = MetadataService::instance()->getRegistry()->getDefinition( $record->LogicalName );

        foreach ( $this->Attributes as $attributeName => $value ) {
            $attrMetaData = $this->metadata->Attributes[ $attributeName ] ?? null;
            if ( $this->isLocalDateAttributeName( $attributeName ) && $attrMetaData instanceof DateTimeAttributeMetadata ) {
                $this->$attributeName = $value;
            } elseif ( $attrMetaData instanceof DateTimeAttributeMetadata ) {
                if ( !$this->getAttributeState()->offsetGet( $attributeName ) ) {
                    if ( !$value ) {
                        $this->SetAttributeValue( $this->getAttributeName( $attributeName ), null );
                    } else {
                        $this->$attributeName = $value;
                    }
                }
            } else {
                if ( str_contains( $attributeName, '_time' ) && $value ) {
                    $dateValue = new \DateTimeImmutable( $value, new DateTimeZone( 'UTC' ) );
                    $dateValue = $this->getCorrectDateTime( $attributeName, $dateValue );
                    $this->SetAttributeValue( $this->getAttributeName( $attributeName ), $dateValue->format( 'Y-m-d\TH:i' ) . 'Z' );
                }
            }
        }
        $record->Attributes = $this->Attributes;
        foreach ( $this->getAttributeState() as $fieldName => $_ ) {
            $record->attributeState[ $fieldName ] = true;
        }

        return $record;
    }

    /**
     * @param XrmEntity $record
     *
     * @return Entity
     * @throws AuthenticationException
     * @throws OrganizationException
     * @throws ToolkitException
     */

    public function createFromEntity( XrmEntity $record ): Entity {
        $result = new Entity();
        $this->Attributes = $record->Attributes;

        $this->metadata = MetadataService::instance()->getRegistry()->getDefinition( $record->LogicalName );
        if ( $this->metadata === null ) {
            return new Entity();
        }

        foreach ( get_object_vars( $record ) as $key => $value ) {
            if ( $key === 'Attributes' ) {
                foreach ( $value as $column => $columnVal ) {
                    [ $alias, $field ] = array_pad( explode( '.', $column ), 2, null );
                    if ( $field ) {
                        $value[ $alias ][ $field ] = $columnVal;
                    }

                    $attrMetaData = $this->metadata->Attributes[ $column ] ?? null;
                    if ( $attrMetaData instanceof DateTimeAttributeMetadata ) {
                        if ( $attrMetaData->DateTimeBehavior->Value === DateTimeBehavior::UserLocal ) {
                            $value[ $column . '_local_time' ] = $this->{$column . '_local_time'};
                            $value[ $column . '_local_date' ] = $this->{$column . '_local_date'};
                            $value[ $column . '_local' ] = $this->{$column . '_local'};
                        }
                        if ( $attrMetaData->DateTimeBehavior->Value === DateTimeBehavior::DateOnly ) {
                            try {
                                $dateValue = new \DateTimeImmutable( $columnVal );
                                $value[ $column ] = $dateValue->format( 'Y-m-d' ) . 'T00:00:00.000Z';
                            } catch ( \Exception ) {
                                $value[ $column ] = null;
                            }
                        }
                    }
                }
            }
            $result->$key = $value;
        }

        return $result;
    }

    /**
     * Gets the formatted value of the attribute.
     *
     * Returns empty string if the entity doesn't have the specified formatted value.
     *
     * @param string $attribute
     *
     * @return string
     */
    public function GetFormattedAttributeValue( string $attribute ): string {
        if ( !array_key_exists( $attribute, $this->FormattedValues ) ) {
            return '';
        }

        return $this->FormattedValues[ $attribute ];
    }

    /**
     * Tells whether specified attribute value exists.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function Contains( string $attribute ): bool {
        return array_key_exists( $attribute, $this->Attributes );
    }

    /**
     * Get correct Date or Time or DateTime for attribute name
     *
     * @param $name
     * @param $dateValue
     *
     * @return \DateTimeImmutable|false
     * @throws \Exception
     */
    private function getCorrectDateTime( $name, $dateValue ) {
        $attributeValue = new \DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
        $attributeName = $this->getAttributeName( $name );

        if ( isset( $this->Attributes[ $attributeName ] ) ) {
            try {
                $attributeValue = new \DateTimeImmutable( $this->Attributes[ $attributeName ] );
            } catch ( \Exception ) {
                $attributeValue = new \DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
            }
        }
        if ( str_contains( $name, '_local_time' ) || str_contains( $name, '_time' ) ) {
            $dateValue = $attributeValue->setTime( $dateValue->format( 'H' ), $dateValue->format( 'i' ), $dateValue->format( 's' ) );
        } elseif ( str_contains( $name, '_local_date' ) ) {
            $dateValue = $attributeValue->setDate( $dateValue->format( 'Y' ), $dateValue->format( 'm' ), $dateValue->format( 'd' ) );
        } else {
            $dateValue = $attributeValue;
        }

        return $dateValue;
    }

    /**
     * Get real attribute name
     *
     * @param string $name
     *
     * @return string
     */
    private function getAttributeName( string $name ): string {
        return str_replace( [ '_local_time', '_local_date', '_local', '_time' ], '', $name );
    }

    /**
     * Is dateTime attribute name
     *
     * @param string $name
     *
     * @return bool
     */
    private function isLocalDateAttributeName( string $name ): bool {
        $dateTimeAttrName = [ '_local_time', '_local_date', '_local', '_time' ];
        foreach ( $dateTimeAttrName as $attr ) {
            if ( str_contains( $name, $attr ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get User or default time zone
     *
     * @return DateTimeZone
     * @throws \Exception
     */
    private function getUserTimeZone(): DateTimeZone {
        if ( class_exists( User::class ) ) {
            $icdsUserTimezone = ( new User() )->timezone();
        } else {
            $icdsUserTimezone = null;
        }

        if ( str_contains( $icdsUserTimezone, 'UTC' ) ) {
            $tzFound = preg_match( '/^UTC([+-])((\d{1,2}[:]\d{1,2})|(\d{1,2}))$/s', $icdsUserTimezone, $matches );
            if ( $tzFound ) {
                $tz = $matches[1] . $matches[2];
            } else {
                $tz = 'UTC';
            }

            return new DateTimeZone( $tz );
        } elseif ( !empty( $icdsUserTimezone ) ) {
            return new DateTimeZone ( $icdsUserTimezone );
        }

        return wp_timezone();
    }

}
