<?php
/*
 * Copyright 2021 AlexaCRM
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

namespace AlexaCRM\Nextgen\API\EndpointsV2;

use AlexaCRM\Nextgen\API\NoContentResponse;
use AlexaCRM\Nextgen\API\ServerErrorResponse;
use AlexaCRM\Nextgen\AdvancedSettings;
use AlexaCRM\Nextgen\SettingsProvider;

/**
 * Updates advanced settings.
 */
class UpdateAdvancedSettings extends AdministrativeEndpoint {

    public string $name = 'advanced_settings';

    public array $methods = [ 'POST' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $req = $request->get_json_params();
        /** @var AdvancedSettings $saved */
        $saved = SettingsProvider::instance()->getSettings( AdvancedSettings::SETTINGS_TYPE_NAME );

        $new = new AdvancedSettings();
        $new->settings = array_merge( $saved->settings, $req['settings'] );

        $updated = SettingsProvider::instance()->persistSettings( $new );

        if ( $updated ) {
            return new NoContentResponse();
        }

        return new ServerErrorResponse( 1, __( 'Failed to update advanced settings.', 'integration-cds' ) );
    }
}
