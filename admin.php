<?php
/*
 * Copyright 2020 AlexaCRM
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

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\CheckRequirementsService;
use AlexaCRM\Nextgen\Forms\Recaptcha\Badge;
use AlexaCRM\Nextgen\Forms\Recaptcha\Size;
use AlexaCRM\Nextgen\Forms\Recaptcha\Theme;
use AlexaCRM\Nextgen\InformationProvider;
use AlexaCRM\Nextgen\RecaptchaProvider;
use function AlexaCRM\Nextgen\getApiDefaults;

$adminSrc = ICDS_DIR . '/front/admin/index.html';
if ( defined( 'ICDS_NEW_ADMIN_URL' ) && ICDS_NEW_ADMIN_URL ) {
    $adminSrc = ICDS_NEW_ADMIN_URL;
}
$output = file_get_contents( $adminSrc );

$output = str_replace( '__ICDS_BASE_URL__', trailingslashit( site_url() ), $output );

$globals['icdsAPIDefaults'] = getApiDefaults();
/**
 * Filters the list of available settings.
 *
 * @param \AlexaCRM\Nextgen\Settings[] $settingsMap Associative array of settings.
 */
$globals['icdsSettings'] = apply_filters( 'integration-cds/admin/settings', [] );

$recaptchaAdapters = RecaptchaProvider::instance()->getAvailableAdapters();
$globals['icdsRecaptchaAdapters'] = array_map(
    function( $key, $value ) {
        return [
            'id' => $key,
            'label' => $value,
        ];
    },
    array_keys( $recaptchaAdapters ),
    array_values( $recaptchaAdapters )
);
$globals['icdsRecaptchaThemes'] = array_values( Theme::getValues() );
$globals['icdsRecaptchaSizes'] = array_values( Size::getValues() );
$globals['icdsRecaptchaBadges'] = array_values( Badge::getValues() );

$globals['icdsWp'] = [
    'adminUrl' => admin_url(),
];

$premiumInstallUrl = '';
if ( !InformationProvider::instance()->isPremiumInstalled() ) {
    $nonce = wp_create_nonce( 'install-plugin_integration-cds-premium' );
    $premiumInstallUrl = admin_url( 'update.php?action=install-plugin&plugin=integration-cds-premium&_wpnonce=' . $nonce );
}

$requirementsErrors = [];
if ( CheckRequirementsService::instance()->hasMandatory() ) {
    $requirementsErrors = CheckRequirementsService::instance()->getErrors()[ CheckRequirementsService::MANDATORY ];
}

$globals['icdsRequirementsErrors'] = $requirementsErrors;

$globals['icdsPremiumInfo'] = [
    // isSolutionInstalled is delivered via GetConnectionStatus.
    'isPremiumInstalled' => InformationProvider::instance()->isPremiumInstalled(),
    'solutionUrl' => InformationProvider::SOLUTION_MARKETPLACE_URL,
    'premiumInstallUrl' => $premiumInstallUrl,
];
$globals['icdsPremiumErrors'] = array_map( function( $error ) {
    return [
        'code' => $error->get_error_code(),
        'message' => $error->get_error_message(),
    ];
}, InformationProvider::instance()->getPremiumErrors() );

/**
 * Filters the list Javascript variable to be registered in admin area.
 *
 * @param array $globals Associative array of variables.
 */
$globals = apply_filters( 'integration-cds/admin/js-variables', $globals );

$globalsJs = json_encode( json_encode( $globals ) );
$js = <<<HTML
<script>
(function() {
    var globals = JSON.parse({$globalsJs})
    for(var key in globals) {
      if(!globals.hasOwnProperty(key)) {
        continue
      }

      window[key] = globals[key]
    }
}())
</script>
HTML;

$extensions = apply_filters( 'integration-cds/admin/extensions', [] );

foreach ( $extensions as $extension ) {
    $js .= "<script type='text/javascript' src='{$extension}'></script>";
}

$placeholder = '<div id="app"></div>';
$placeholderPos = strpos( $output, $placeholder );
$output = substr_replace( $output, $js, $placeholderPos, 0 );

echo $output;
