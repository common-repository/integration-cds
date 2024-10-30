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

use AlexaCRM\Nextgen\ConnectionService;
use AlexaCRM\Nextgen\Forms\Recaptcha\RecaptchaSettings;
use AlexaCRM\Nextgen\LoggerProvider;
use AlexaCRM\Nextgen\MetadataService;
use AlexaCRM\Nextgen\Twig\Util;
use AlexaCRM\Xrm\Entity as XrmEntity;
use AlexaCRM\Xrm\EntityReference;
use AlexaCRM\Xrm\Metadata\BigIntAttributeMetadata;
use AlexaCRM\Xrm\Metadata\BooleanAttributeMetadata;
use AlexaCRM\Xrm\Metadata\DateTimeAttributeMetadata;
use AlexaCRM\Xrm\Metadata\DateTimeBehavior;
use AlexaCRM\Xrm\Metadata\DecimalAttributeMetadata;
use AlexaCRM\Xrm\Metadata\DoubleAttributeMetadata;
use AlexaCRM\Xrm\Metadata\FileAttributeMetadata;
use AlexaCRM\Xrm\Metadata\ImageAttributeMetadata;
use AlexaCRM\Xrm\Metadata\IntegerAttributeMetadata;
use AlexaCRM\Xrm\Metadata\LookupAttributeMetadata;
use AlexaCRM\Xrm\Metadata\MoneyAttributeMetadata;
use Monolog\Logger;

/**
 * Represents a form in Twig. To be used by the front-end app to render an HTML form.
 */
class CustomFormModel extends FormModel {

    /**
     * Form settings specified in the form declaration.
     *
     * @var CustomFormAttributes
     */
    public $formSettings;

    /**
     * Whether reCAPTCHA is configured and enabled for this form.
     *
     * @var bool
     */
    public $isRecaptchaEnabled;

    /**
     * Contains reCAPTCHA settings.
     *
     * @var RecaptchaSettings
     */
    public $recaptchaSettings;

    /**
     * Returns labels for specified attributes.
     *
     * @param array $attributes
     *
     * @return array
     * @throws \AlexaCRM\WebAPI\OData\AuthenticationException
     * @throws \AlexaCRM\WebAPI\OrganizationException
     * @throws \AlexaCRM\WebAPI\ToolkitException
     */
    public function getLabels( array $attributes ): array {
        $md = MetadataService::instance()->getRegistry()->getDefinition( $this->formSettings->entity )->Attributes;

        $labelList = array_map( function( $attributeName ) use ( $md ) {
            return $md[ $attributeName ]->DisplayName->UserLocalizedLabel->Label ?? $attributeName;
        }, $attributes );

        return $labelList;
    }

    /**
     * Prepares incoming form values for further processing.
     *
     * @param array $data
     *
     * @return array
     */
    public function prepareData( array $data = [] ): array {
        $result = [];

        $metadata = MetadataService::instance()->getRegistry()->getDefinition( $this->formSettings->entity );

        $data = array_intersect_key(
            $data,
            /**
             * Allows altering the list of accepted fields.
             *
             * @param array $keys List of form fields.
             */
            array_flip( apply_filters( 'integration-cds/forms/fields', array_keys( $data ) ) )
        );

        foreach ( $data as $attributeName => $value ) {
            if ( !isset( $metadata->Attributes[ $attributeName ] ) ) {
                $result[ $attributeName ] = $value;
                continue;
            }

            // Empty value sets the field to NULL.
            if ( $value === null || ( is_string( $value ) && trim( $value ) === '' ) ) {
                $result[ $attributeName ] = null;
                continue;
            }

            $attrMd = $metadata->Attributes[ $attributeName ];

            if ( $attrMd instanceof IntegerAttributeMetadata || $attrMd instanceof BigIntAttributeMetadata ) {
                // FIXME: BigInt may overflow on 32-bit systems.
                $result[ $attributeName ] = (int)$value;
                continue;
            }

            if ( $attrMd instanceof MoneyAttributeMetadata || $attrMd instanceof DoubleAttributeMetadata
                 || $attrMd instanceof DecimalAttributeMetadata ) {
                $result[ $attributeName ] = (float)$value;
                continue;
            }

            /*
             * Lookup values are accepted as follow:
             *  - colon-separated logical name and ID, e.g. original HTML markup looks like
             *   ```
             *   <option value="contact:00000000-0000-0000-0000-000000000000">Display Name</option>
             *   ```
             *  - JSON serialized string containing suitable fields, e.g.
             *   ```
             *   <option value='{ "LogicalName":"contact", "Id":"00000000-0000-0000-0000-000000000000" }'>Display Name</option>
             *   ```
             */
            if ( $attrMd instanceof LookupAttributeMetadata ) {
                $v = json_decode( $value );

                if ( json_last_error() === JSON_ERROR_NONE ) {
                    $v = Util::toEntityReference( $v );

                    if ( $v !== null ) {
                        $result[ $attributeName ] = $v;
                        continue;
                    }
                }

                $v = explode( ':', $value );
                if ( !is_array( $v ) || count( $v ) !== 2 ) {
                    continue;
                }

                $result[ $attributeName ] = new EntityReference( $v[0], $v[1] );
                continue;
            }

            /**
             * Prepare DateOnly dates. (YYYY-MM-DD part only)
             */
            if ( $attrMd instanceof DateTimeAttributeMetadata && $attrMd->DateTimeBehavior->Value === DateTimeBehavior::DateOnly ) {
                $result[ $attributeName ] = substr( $value, 0, 10 );
                continue;
            }

            /*
             * Handle booleans that are delivered as integers, e.g. radio.
             */
            if ( $attrMd instanceof BooleanAttributeMetadata && is_int( $value ) ) {
                $result[ $attributeName ] = $value === 1;
                continue;
            }

            /**
             * Decode base64-encoded file contents.
             */
            if ( $attrMd instanceof FileAttributeMetadata || $attrMd instanceof ImageAttributeMetadata ) {
                $result[ $attributeName ] = base64_decode( $value );
                continue;
            }

            $result[ $attributeName ] = $value;
        }

        return $result;
    }

    /**
     * Perform data submission.
     *
     * @param array $data
     * @param EntityReference|null $entityReference Dataverse row reference which is being updated.
     *
     * @return SubmissionResult
     */
    public function submitData( array $data, ?EntityReference $entityReference = null ): SubmissionResult {
        $record = new XrmEntity($this->formSettings->entity);
        $logger = LoggerProvider::instance()->getLogger();
        $formClone = clone $this;

        foreach ( $data as $attributeName => $value ) {
            $record[ $attributeName ] = $value;
        }

        /*
         * Set true by default.
         */
        $validationResult['status'] = true;

        /**
         * Filters the default form validation.
         *
         * @param bool $validationResult
         * @param array $data map of fields received from the form.
         * @param CustomFormModel $this
         */
        $validationResult = apply_filters( 'integration-cds/forms/validate', $validationResult, $data, $formClone );

        if ( $validationResult['status'] ) {
            try {
                $client = ConnectionService::instance()->getClient();

                if ( $this->formSettings->mode === CustomFormAttributes::MODE_UPDATE && $entityReference instanceof EntityReference ) {
                    $record->Id = $entityReference->Id;
                    $client->Update( $record );

                    $logger->debug( __( 'Updated entity record with custom form', 'integration-cds' ), [
                        'entity_reference' => $entityReference,
                        'record' => $record,
                    ] );
                } else {
                    $id = $client->Create( $record );
                    $record->Id = $id;

                    $logger->debug( __( 'Created entity record with custom form', 'integration-cds' ), [
                        'record' => $record,
                    ] );
                }

                $result = new SubmissionResult( true );
                $result->reference = $record->ToEntityReference();

                /**
                 * Fires after custom form was successfully submitted.
                 *
                 * @param CustomFormModel $this
                 * @param XrmEntity $record
                 */
                do_action( 'integration-cds/forms/submit-success', $formClone, $record );
            } catch ( \Exception $e ) {
                $logger->error( __('Failed to submit custom form', 'integration-cds'), [
                    'record' => $record,
                    'error' => $e,
                ] );

                $result = new SubmissionResult( false );
                $result->errorMessage = $e->getMessage();
                $result->exception = $e;

                /**
                 * Fires if error occurs while submitting custom form.
                 *
                 * @param CustomFormModel $this
                 * @param XrmEntity $record
                 * @param \Exception $e
                 */
                do_action( 'integration-cds/forms/submit-error', $formClone, $record, $e );
            }
        } else {
            $result = $this->setCustomValidationResult( $validationResult, $logger, $record );
        }

        return $result;
    }

    /**
     * @param array $validationResult
     * @param Logger $logger
     * @param XrmEntity $record
     *
     * @return SubmissionResult
     */
    private function setCustomValidationResult(array $validationResult, Logger $logger, XrmEntity $record): SubmissionResult {
        $errorMessage = $validationResult['payload'] ?? '';
        $logger->error( __( 'Failed to submit custom form', 'integration-cds' ), [
            'record' => $record,
            'error' => $errorMessage,
        ] );

        $result = new SubmissionResult( false );
        $result->errorMessage = $errorMessage;
        $result->exception = null;
        return $result;
    }

    /**
     * @param array $files
     * @param EntityReference $entityReference
     *
     * @return SubmissionResult
     */
    public function submitFiles( array $files, EntityReference $entityReference ): SubmissionResult {
        // TODO: Implement submitFiles() method.

        $result = new SubmissionResult( true );
        $result->reference = $entityReference;

        return $result;
    }

    /**
     * Generates a form authentication reference.
     *
     * @param EntityReference|null $ref
     *
     * @return string
     */
    public function getAuthRef( EntityReference $ref = null ): string {
        $id = '00000000-0000-0000-0000-000000000000';
        if ( $ref instanceof EntityReference ) {
            $id = strtolower( $ref->Id );
        }

        $recaptcha = $this->formSettings->recaptcha? 'recaptcha' : 'norecaptcha';

        $str = sprintf( '%s,%s,%s(%s)', $this->formSettings->mode, $this->formSettings->entity, $recaptcha, $id );

        return FormAuthUtil::generateRefCode( $str );
    }

    /**
     * Checks whether the form authentication reference is legit.
     *
     * @param string $code Base64-encoded form authentication reference.
     * @param EntityReference|null $ref
     *
     * @return bool
     */
    public function validateAuthRef( string $code, EntityReference $ref = null ): bool {
        $sample = $this->getAuthRef( $ref );

        return $code === $sample;
    }

}
