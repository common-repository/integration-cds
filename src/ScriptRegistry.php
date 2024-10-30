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

namespace AlexaCRM\Nextgen;

/**
 * Front-end script manager based on WordPress-native WP_Scripts.
 */
class ScriptRegistry extends \WP_Scripts {

    use SingletonTrait;

    /**
     * Localizes a script, only if the script has already been added.
     *
     * @param string $handle      Name of the script to attach data to.
     * @param string $object_name Name of the variable that will contain the data.
     * @param array  $l10n        Array of data to localize.
     *
     * @return bool True on success, false on failure.
     */
    public function localize( $handle, $object_name, $l10n ): bool {
        if ( $handle === 'jquery' ) {
            $handle = 'jquery-core';
        }

        if ( is_array( $l10n ) && isset( $l10n['l10n_print_after'] ) ) { // back compat, preserve the code in 'l10n_print_after' if present.
            $after = $l10n['l10n_print_after'];
            unset( $l10n['l10n_print_after'] );
        }

        foreach ( (array) $l10n as $key => $value ) {
            if ( !is_string( $value ) ) {
                continue;
            }

            $l10n[ $key ] = html_entity_decode( $value, ENT_QUOTES, 'UTF-8' );
        }

        $script = "var $object_name = " . wp_json_encode( $l10n ) . ';';

        if ( ! empty( $after ) ) {
            $script .= "\n$after;";
        }

        $data = $this->get_data( $handle, 'data' );

        if ( ! empty( $data ) ) {
            $script = "$data\n$script";
        }

        return $this->add_data( $handle, 'data', $script );
    }

}
