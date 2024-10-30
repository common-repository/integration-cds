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

namespace AlexaCRM\Nextgen\API\Endpoints;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\API\AdministrativeEndpoint;
use AlexaCRM\Nextgen\API\BadRequestResponse;
use AlexaCRM\Nextgen\API\NoContentResponse;
use AlexaCRM\Nextgen\Forms\Recaptcha\RecaptchaSettings;
use AlexaCRM\Nextgen\Forms\Recaptcha\RecaptchaValidator;

/**
 * Provides an endpoint to check reCAPTCHA settings before they are committed into the settings storage.
 */
class CheckRecaptcha extends AdministrativeEndpoint {

    public string $name = 'check_recaptcha';

    public array $methods = [ 'POST' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $params = $request->get_json_params();

        $recaptchaSettings = new RecaptchaSettings();
        $recaptchaSettings->siteKey = $params['settings']['siteKey'];
        $recaptchaSettings->secretKey = $params['settings']['secretKey'];
        $recaptchaSettings->type = $params['settings']['type'];

        $recaptchaValidator = new RecaptchaValidator( $recaptchaSettings );

        $isRecaptchaValid = $recaptchaValidator->validate( $params['token'] );
        if ( $isRecaptchaValid ) {
            return new NoContentResponse();
        }

        return new BadRequestResponse( 1, __( 'reCAPTCHA test error: ' . implode( '; ', $recaptchaValidator->lastResponse->getErrorCodes() ) ), 'integration-cds' );
    }
}
