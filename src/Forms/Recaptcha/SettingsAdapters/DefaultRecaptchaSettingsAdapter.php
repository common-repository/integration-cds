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

namespace AlexaCRM\Nextgen\Forms\Recaptcha\SettingsAdapters;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\Forms\Recaptcha\RecaptchaSettings;
use AlexaCRM\Nextgen\Forms\Recaptcha\RecaptchaSettingsAdapterInterface;
use AlexaCRM\Nextgen\SettingsProvider;

/**
 * Provide default global plugin setting
 */
class DefaultRecaptchaSettingsAdapter implements RecaptchaSettingsAdapterInterface {

    /**
     * @return string
     */
    public function getLabel(): string {
        return 'Do not import, set manually';
    }

    /**
     * @return RecaptchaSettings
     */
    public function getSettings(): RecaptchaSettings {
        /** @var RecaptchaSettings $settings */
        $settings = SettingsProvider::instance()->getSettings( 'recaptcha' );

        return $settings;
    }

    /**
     * @return bool
     */
    public function isAvailable(): bool {
        return true;
    }
}
