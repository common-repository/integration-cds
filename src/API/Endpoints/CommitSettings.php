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
use AlexaCRM\Nextgen\SettingsProvider;

/**
 * Provides an endpoint which allows persisting plugin settings.
 */
class CommitSettings extends AdministrativeEndpoint {

    public string $name = 'settings';

    public array $methods = [ 'PUT' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_Error|\WP_REST_Response
     */
    public function respond( \WP_REST_Request $request ) {
        $prefs = $request->get_json_params();

        if ( SettingsProvider::instance()->importSettings( $prefs ) ) {
            return new NoContentResponse();
        }

        return new BadRequestResponse( 1, __( 'Failed to save the settings. Please try again later.', 'integration-cds-premium' ) );
    }

}
