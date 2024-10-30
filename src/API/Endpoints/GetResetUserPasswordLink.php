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

use AlexaCRM\Nextgen\API\AuthenticatedEndpoint;
use AlexaCRM\Nextgen\API\BadRequestResponse;

/**
 * Provides an endpoint to retrieve a user password reset link.
 */
class GetResetUserPasswordLink extends AuthenticatedEndpoint {

    public string $name = 'reset_password_link';

    public array $methods = [ 'GET' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $params = $request->get_params();

        if ( !empty( $params['id'] ) ) {
            $user = get_user_by( 'id', $params['id'] );
        } elseif ( !empty( $params['email'] ) ) {
            $user = get_user_by( 'email', $params['email'] );
        } elseif ( !empty( $params['login'] ) ) {
            $user = get_user_by( 'login', $params['login'] );
        } else {
            return new BadRequestResponse( 2, "Either user id, login, or email must be specified." );
        }

        if ( $user === false ) {
            return new BadRequestResponse( 3, "User not found." );
        }

        $key = get_password_reset_key( $user );

        if ( is_wp_error( $key ) ) {
            return $key;
        }

        $login = $user->user_login;
        $locale = get_user_locale( $user );

        return new \WP_REST_Response( [
            'link' => network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $login ), 'login' ) . '&wp_lang=' . $locale,
        ] );
    }
}
