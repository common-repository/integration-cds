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

use AlexaCRM\Nextgen\DetectBrowserUtil;
use AlexaCRM\Nextgen\RecaptchaProvider;
use AlexaCRM\Xrm\Entity;
use AlexaCRM\Xrm\EntityReference;

/**
 * Helps rendering custom forms on front-end.
 */
class CustomFormRenderer {

    const FORM_END_TAG = '</form>';

    /**
     * Html form code.
     *
     * @var string
     */
    protected string $form;

    /**
     * CustomFormRenderer constructor.
     *
     * @param string $form Raw html form code.
     */
    public function __construct( $form ) {
        $this->form = $form;
    }

    /**
     * Renders an html form.
     *
     * @param CustomFormAttributes $attributes Additional attributes to be rendered within the form.
     */
    public function render( CustomFormAttributes $attributes ): void {
        $model = new CustomFormModel();
        $model->formSettings = $attributes;

        $fieldsToRender = [
            'entity',
            'mode',
            'record',
            'recaptcha',
        ];

        $additionalFields = [];
        foreach ( $attributes as $fieldName => $fieldValue ) {
            if ( empty( $fieldValue ) ) {
                continue;
            }

            if ( !in_array( $fieldName, $fieldsToRender, true ) ) {
                continue;
            }

            $additionalFields[] = $this->renderHiddenField( $fieldName, $fieldValue );
        }

        $model->isRecaptchaEnabled = $attributes->recaptcha;
        if ( $attributes->recaptcha ) {
            $recaptchaSettings = RecaptchaProvider::instance()->getSettings();
            unset( $recaptchaSettings->secretKey, $recaptchaSettings->scoreThreshold, $recaptchaSettings->adapterName );
            $model->recaptchaSettings = $recaptchaSettings;

            $repl = <<<HTML
<VueRecaptcha ref="recaptcha"
    @loaded="onRecaptchaLoaded"
    @solved="onRecaptchaSolved"
    @solved-invisible="onRecaptchaInvisibleSolved"
    @failed="onRecaptchaFailed"
    :type="form.recaptchaSettings.type" :site-key="form.recaptchaSettings.siteKey"
    :theme="form.recaptchaSettings.theme" :size="form.recaptchaSettings.size" :badge="form.recaptchaSettings.badge" />
HTML;
            $this->form = str_replace( '<recaptcha>', $repl, $this->form );
        } else {
            $this->form = str_replace( '<recaptcha>', '', $this->form );
        }

        $this->form = substr_replace(
            $this->form,
            implode( $additionalFields ),
            strpos( $this->form, static::FORM_END_TAG ),
            0
        );

        $modelId = $model->id;

        if ( ! DetectBrowserUtil::isBrowserSupported() ) {
            echo DetectBrowserUtil::getBrowserNotSupportedHtml();
        } else {
            echo '<div id="icds-custom-form-container-' . $modelId . '"></div>';
        }

        wp_enqueue_script( 'icds/custom-form' );
        wp_enqueue_style( 'icds/public-forms' );

        add_action( 'wp_footer', function () use ( $model ) {
            echo /** @lang HTML */
            <<<TEMPLATE
<script type="text/x-template" id="icds-tpl-custom-form-code-{$model->id}">
    {$this->form}
</script>
TEMPLATE;

            $modelEncoded = json_encode( $model );
            $authRefJson = json_encode( $model->getAuthRef( $model->formSettings->record ) );

            echo /** @lang HTML */
            <<<SCRIPT
<script>
    'use strict';
    (function(window, modelId) {
        window.icdsCustomForms = window.icdsCustomForms || {};
        window.icdsCustomForms[modelId] = {$modelEncoded};

        window.icdsAuthRefs = window.icdsAuthRefs || {};
        window.icdsAuthRefs[modelId] = {$authRefJson};
    }(window, '{$model->id}'));
</script>
SCRIPT;
        } );
    }

    /**
     * Determines whether the html code of the form is valid.
     */
    public function checkForm(): bool {
        $formEnd = strpos( $this->form, static::FORM_END_TAG );

        return !( $formEnd === false );
    }

    /**
     * Renders a hidden input field for the html form.
     *
     * @param string $name Field name.
     * @param mixed $value Field value.
     *
     * @return string
     */
    private function renderHiddenField( string $name, $value ): string {
        if ( $value instanceof Entity ) {
            $valueString = $value->Id;
        } elseif ( $value instanceof EntityReference ) {
            $valueString = $value->Id;
        } else {
            $valueString = (string)$value;
        }

        return '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $valueString ) . '">';
    }

}
