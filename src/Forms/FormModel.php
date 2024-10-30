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

namespace AlexaCRM\Nextgen\Forms;

use AlexaCRM\Xrm\Entity;
use AlexaCRM\Xrm\EntityReference;
use AlexaCRM\Xrm\Metadata\AttributeMetadata;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

abstract class FormModel {

    /**
     * Unique identifier of the form instance.
     *
     * Used to address relevant form elements in DOM.
     */
    public string $id;

    /**
     * Collection of attributes used on the form.
     *
     * @var string[]
     */
    public array $formAttributes = [];

    /**
     * Collection of required attributes of the target entity.
     *
     * Computed from Business/System Required attributes and Required overrides.
     *
     * Doesn't include attributes included into Optional overrides.
     *
     * @var string[]
     */
    public array $requiredAttributes = [];

    /**
     * Metadata for attributes present on the form.
     *
     * @var AttributeMetadata[]
     */
    public array $attributeMetadata = [];

    /**
     * FormModel constructor.
     */
    public function __construct() {
        $this->id = uniqid();
    }

    /**
     * Returns a map of entity record values present on the form.
     *
     * @param Entity|null $record
     *
     * @return array
     */
    public function getAttributeValues( Entity $record = null ): array {
        $values = [];

        foreach ( $this->formAttributes as $attribute ) {
            $values[ $attribute ] = ( $record instanceof Entity ) ? $record->GetAttributeValue( $attribute ) : null;
        }

        return $values;
    }

    /**
     * Returns a map of entity record values present on the form.
     *
     * @param Entity|null $record
     * @param string $attribute
     *
     * @return mixed|null
     */
    public function getAttributeFormattedValue( Entity $record = null, string $attribute = '' ) {
        if ( !$record instanceof Entity ) {
            return null;
        }

        if ( !in_array( $attribute, $this->formAttributes ) ) {
            return null;
        }

        $formattedValue = $record->GetFormattedAttributeValue( $attribute );

        if ( !empty( $formattedValue ) ) {
            return $formattedValue;
        }

        return $record->GetAttributeValue( $attribute );
    }

    /**
     * Returns labels for specified attributes.
     *
     * @param string[] $attributes
     *
     * @return array
     */
    abstract public function getLabels( array $attributes ): array;

    /**
     * Prepares incoming form values for further processing.
     *
     * @param array $data
     *
     * @return array
     */
    abstract public function prepareData( array $data ): array;

    /**
     * Perform data submission.
     *
     * @param array $data
     * @param EntityReference|null $entityReference Dataverse row reference which is being updated.
     *
     * @return SubmissionResult
     */
    abstract public function submitData( array $data, EntityReference $entityReference = null ): SubmissionResult;

    /**
     * Perform data submission.
     *
     * @param array $files
     * @param EntityReference $entityReference Dataverse row reference which is being updated.
     *
     * @return SubmissionResult
     */
    abstract public function submitFiles( array $files, EntityReference $entityReference ): SubmissionResult;

}
