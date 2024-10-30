<?php

namespace AlexaCRM\Nextgen;

use AlexaCRM\StrongSerializer\Reference;
use AlexaCRM\Xrm\Label;
use AlexaCRM\Xrm\Metadata\EntityMetadata;

/**
 * Class EntityFilter
 * Used to create extra filter for EntityDefinitions request
 *
 * @package AlexaCRM\Nextgen
 */
class EntityFilter {

    /**
     * Represents a "Default" filter
     */
    public const ENTITY_DEFAULT_FILTER = 'Default';

    /**
     * Represents a "Custom" filter
     */
    public const ENTITY_CUSTOM_FILTER = 'Custom';

    /**
     * Represents a "Managed" filter
     */
    public const ENTITY_MANAGED_FILTER = 'Managed';

    /**
     * Represents an "All" filter
     */
    public const ENTITY_ALL_FILTER = 'All';

    /**
     * List of entities for "Default" filter
     */
    public const DEFAULT_ENTITIES = [
        'contact',
        'account',
        'alexacrm_case',
        'customeraddress',
        'appointment',
        'activitymimeattachment',
        'businessunit',
        'transactioncurrency',
        'email',
        'template',
        'fax',
        'letter',
        'feedback',
        'mailbox',
        'organization',
        'phonecall',
        'position',
        'recurringappointmentmaster',
        'task',
        'team',
        'teamtemplate',
        'systemuser',
    ];

    /**
     * Determines if the filter matches with provided EntityMetadata
     *
     * @param EntityMetadata $entityMetadata
     *
     * @return bool
     */
    private static function filterEntity( EntityMetadata $entityMetadata ): bool {
        $filter = false;

        switch ( self::getFilterType() ) {
            // All
            case self::ENTITY_ALL_FILTER:
                $filter = true;
                break;
            // Custom
            case self::ENTITY_CUSTOM_FILTER:
                $filter = $entityMetadata->IsCustomEntity === true;
                break;
            // Managed
            case self::ENTITY_MANAGED_FILTER:
                $filter = $entityMetadata->IsManaged === true;
                break;
            // Default
            case self::ENTITY_DEFAULT_FILTER:
                $filter = in_array( $entityMetadata->LogicalName, self::DEFAULT_ENTITIES );
                break;
        }

        return $filter;
    }

    /**
     * Returns display name of the entity in specified format
     *
     * @param Label $displayName
     * @param string $logicalName
     * @param bool $isDropdown
     *
     * @return string
     */
    public static function getDisplayName( Label $displayName, string $logicalName, bool $isDropdown = false ): string {
        if ( $isDropdown ) {
            return $logicalName;
        }

        $display = $logicalName;

        if ( !empty( $displayName ) && !empty( $displayName->UserLocalizedLabel ) ) {
            $display = $displayName->UserLocalizedLabel->Label;
        }

        if ( !empty( $display ) ) {
            $display .= " ({$logicalName})";
        }

        return $display;
    }

    /**
     * @param array $entityList
     * @param Reference $ref
     * @param $deserializer
     * Returns array of filtered entities
     * @param $extraEntity
     * @param bool $isDropdown
     *
     * @return array
     */
    public static function getFilteredEntities( array $entityList, Reference $ref, $deserializer, $extraEntity, $isDropdown = false ): array {
        $filtered = [];
        foreach ( $entityList as $object ) {
            /** @var EntityMetadata $entityMetadata */
            $entityMetadata = $deserializer->deserialize( $object, $ref );
            if ( self::filterEntity( $entityMetadata ) === true ) {
                $filtered[ $entityMetadata->LogicalName ] = self::getDisplayName( $entityMetadata->DisplayName, $entityMetadata->LogicalName, $isDropdown );
            }
        }

        $filtered = self::addExtraEntity($extraEntity, $filtered);
        return $filtered;
    }

    /**
     * Filter type getter
     *
     * @return string
     */
    public static function getFilterType(): string {
        return AdvancedSettingsProvider::instance( 'ICDS_ENTITY_FILTER' )->getValue();
    }

    /**
     * @param $extraEntity
     * @param $entityList
     * Add extra entity to the provided entity list
     * @return mixed
     */
    public static function addExtraEntity( $extraEntity, $entityList ) {
        if ( !empty( $extraEntity ) ) {
            $allEntities = MetadataService::instance()->getEntitiesList();
            if ( !isset( $entityList[ $extraEntity ] ) && isset( $allEntities[ $extraEntity ] ) ) {
                $entityList[ $extraEntity ] = $allEntities[ $extraEntity ];
            }
        }

        return $entityList;
    }
}
