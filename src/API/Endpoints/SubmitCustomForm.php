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

namespace AlexaCRM\Nextgen\API\Endpoints;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\API\BadRequestResponse;
use AlexaCRM\Nextgen\API\Endpoint;
use AlexaCRM\Nextgen\ConnectionService;
use AlexaCRM\Nextgen\Forms\CustomFormAttributes;
use AlexaCRM\Nextgen\Forms\CustomFormModel;
use AlexaCRM\Nextgen\Forms\Processor;
use AlexaCRM\Nextgen\Forms\SubmissionResult;
use AlexaCRM\Nextgen\RecaptchaProvider;
use AlexaCRM\Xrm\EntityReference;
use WP_Error;

/**
 * Provides an endpoint to submit form data.
 */
class SubmitCustomForm extends Endpoint {

    /**
     * Header used to pass record binding information to enable form update capabilities.
     */
    const RECORD_HEADER = 'X-ICDS-RecordId';

    /**
     * Header used to pass form authentication reference.
     */
    const AUTHREF_HEADER = 'X-ICDS-FormAuthRef';

    /**
     * Header used to deliver a reCAPTCHA token together with the form submission.
     */
    const RECAPTCHA_HEADER = 'X-ICDS-Recaptcha';

    /**
     * Endpoint name.
     */
    public string $name = 'custom_form_submission';

    /**
     * List of supported HTTP methods.
     */
    public array $methods = [ 'POST' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return mixed
     */
    public function respond( \WP_REST_Request $request ) {
        if ( !ConnectionService::instance()->isAvailable() ) {
            return new WP_Error( 1, __( 'The website is not configured to process the submission.', 'integration-cds' ) );
        }

        try {
            $formData = $request->get_json_params();

            $model = new CustomFormModel();
            $model->formSettings = CustomFormAttributes::createFromArray( $formData );
            if ( $model->formSettings->mode === CustomFormAttributes::MODE_UPDATE && !isset( $formData['record'] ) ) {
                $res = new SubmissionResult( false );
                $res->errorMessage = __( 'Record for update not specified.', 'integration-cds' );

                return $res;
            }

            $processor = new Processor( $model );

            $boundRecordRaw = $request->get_header( static::RECORD_HEADER );
            $ref = null;
            if ( $boundRecordRaw !== null && ( $boundRecordRaw = json_decode( $boundRecordRaw ) ) !== null ) {
                $ref = new EntityReference( $boundRecordRaw->LogicalName, $boundRecordRaw->Id );
                $processor->bindRecord( $ref );
            }

            $authRef = $request->get_header( static::AUTHREF_HEADER );
            if ( $authRef === null || !$model->validateAuthRef( $request->get_header( static::AUTHREF_HEADER ), $ref ) ) {
                $res = new SubmissionResult( false );
                $res->errorMessage = __( 'Failed to authenticate the form submission.', 'integration-cds' );

                return $res;
            }

            $recaptcha = RecaptchaProvider::instance();
            if ( $model->formSettings->recaptcha ) {
                $token = $request->get_header( static::RECAPTCHA_HEADER );
                $isSolved = $recaptcha->getValidator()->validate( $token );
                if ( !$isSolved ) {
                    return new BadRequestResponse( 1, __( 'Invalid reCAPTCHA token.', 'integration-cds' ) );
                }
            }

            unset( $formData['entity'], $formData['mode'], $formData['record'] );

            return $processor->process( $formData );
        } catch ( \Exception $e ) {
            return new WP_Error( 1, $e->getMessage() );
        }
    }

}
