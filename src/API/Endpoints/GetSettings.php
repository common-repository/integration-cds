<?php
/**
 * Copyright 2022 AlexaCRM
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
use AlexaCRM\Nextgen\SettingsProvider;

/**
 * Provides an endpoint to retrieve plugin settings.
 */
class GetSettings extends AdministrativeEndpoint {

    /**
     * @var string
     */
    public string $name = 'settings';

    public array $methods = [ 'GET' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_Error|\WP_REST_Response
     */
    public function respond( \WP_REST_Request $request ) {
        $result = [];

        if ( $settings = $this->getConnectionSettings() ) {
            $result['connection'] = $settings;
        }

        return new \WP_REST_Response( $result );
    }

    /**
     * Returns predefined list of settings
     * @return array
     */
    private function getConnectionSettings(): array {
        $propertiesToReturn = [
            'instanceURI',
            'instanceType',
            'authenticationType',
            'skipCertificateVerification',
        ];

        $settings = [];
        $connectionSettings = SettingsProvider::instance()->getSettings( 'connection' );

        foreach ( $propertiesToReturn as $prop ) {
            if ( isset( $connectionSettings->{$prop} ) ) {
                $settings[ $prop ] = $connectionSettings->{$prop};
            }
        }

        return $settings;
    }
}
