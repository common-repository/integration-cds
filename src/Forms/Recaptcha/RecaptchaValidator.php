<?php
/**
 * Copyright 2018 AlexaCRM
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

namespace AlexaCRM\Nextgen\Forms\Recaptcha;

use ReCaptcha\Response;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Validates user response using provided reCAPTCHA settings.
 */
class RecaptchaValidator {

    protected RecaptchaSettings $settings;

    public ?Response $lastResponse = null;

    /**
     * RecaptchaValidator constructor.
     *
     * @param RecaptchaSettings $settings
     */
    public function __construct( RecaptchaSettings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Allows to override settings with specified ones.
     *
     * @param RecaptchaSettings $settings
     *
     * @return RecaptchaValidator Returns a new copy of the validator.
     */
    public function withSettings( RecaptchaSettings $settings ): RecaptchaValidator {
        $newInstance = clone $this;

        foreach ( $settings as $propName => $propValue ) {
            if ( !in_array( $propName, RecaptchaSettings::READONLY, true ) && !is_null( $propValue ) ) {
                $newInstance->settings->$propName = $propValue;
            }
        }

        $newInstance->lastResponse = null;

        return $newInstance;
    }

    /**
     * Validates the given reCAPTCHA response token against reCAPTCHA verify endpoint.
     *
     * @param string $gRecaptchaResponse The user response token provided by reCAPTCHA.
     *
     * @return bool
     */
    public function validate( string $gRecaptchaResponse ): bool {
        $recaptcha = new \ReCaptcha\ReCaptcha( $this->settings->secretKey );

        if ( $this->settings->type === Type::V3 ) {
            $recaptcha->setExpectedAction( $this->settings->action );
            $recaptcha->setScoreThreshold( $this->settings->scoreThreshold );
        }

        $this->lastResponse = $recaptcha->verify( $gRecaptchaResponse );

        return $this->lastResponse->isSuccess();
    }

}
