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

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Xrm\EntityReference;

/**
 * Processes submissions to forms.
 */
class Processor {

    /**
     * Form model which contains a description of the form, its requirements, etc.
     */
    protected FormModel $model;

    /**
     * Dataverse row reference which is being updated.
     */
    protected ?EntityReference $reference = null;

    /**
     * Processor constructor.
     *
     * @param FormModel $model
     *
     */
    public function __construct( FormModel $model ) {
        $this->model = $model;
    }

    /**
     * Binds the record to the processor.
     *
     * The specified record will be updated with submitted values.
     *
     * @param EntityReference $reference
     */
    public function bindRecord( EntityReference $reference ): void {
        $this->reference = $reference;
    }

    /**
     * Processes submitted data.
     *
     * @param array $data
     *
     * @return SubmissionResult
     */
    public function process( array $data = [] ): SubmissionResult {
        $trippedFields = $this->enforceRequiredFields( $data );

        if ( count( $trippedFields ) > 0 ) {
            $result = new SubmissionResult( false );
            $result->trippedAttributes = $trippedFields;

            $labelList = $this->model->getLabels( $trippedFields );

            $result->errorMessage = sprintf( __( 'You must provide a value for %s.', 'integration-cds' ), implode( ', ', $labelList ) );

            return $result;
        }

        $data = $this->model->prepareData( $data );

        return $this->model->submitData( $data, $this->reference );
    }

    /**
     * Processes submitted files.
     *
     * @param array $files
     * @param EntityReference|null $reference
     *
     * @return SubmissionResult
     */
    public function sendFiles( array $files = [], EntityReference $reference = null ): SubmissionResult {
        return $this->model->submitFiles( $files, $reference );
    }

    /**
     * Returns a collection of absent required fields.
     *
     * Takes into account Required and Optional overrides.
     *
     * @param array $data
     *
     * @return string[]
     */
    protected function enforceRequiredFields( $data = [] ): array {
        $trippedAttributes = [];
        foreach ( $this->model->requiredAttributes as $attributeName ) {
            if ( !array_key_exists( $attributeName, $data ) || $data[ $attributeName ] === null ) {
                $trippedAttributes[] = $attributeName;
            }
        }

        return $trippedAttributes;
    }

}
