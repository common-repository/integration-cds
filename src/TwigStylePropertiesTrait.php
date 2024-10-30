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

namespace AlexaCRM\Nextgen;

use AlexaCRM\Nextgen\Twig\Util;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provides ability to access class members in snake_case style, Twig style.
 */
trait TwigStylePropertiesTrait {

    /**
     * Allows accessing class properties in snake_case style.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get( string $name ) {
        $camelName = Util::snakeToCamel( $name );

        return $this->{$camelName};
    }

    /**
     * Allows accessing class methods in snake_case style.
     *
     * Twig will hit __isset() first. If no property exists, it will either directly
     * call the matching method if one exists, or it will honor __call().
     *
     * @param string $name
     * @param $args array
     *
     * @return mixed
     */
    public function __call( string $name, $args ) {
        $camelName = Util::snakeToCamel( $name );

        if ( !method_exists( $this, $camelName ) ) {
            return null;
        }

        return $this->{$camelName}();
    }

    /**
     * Determines whether a class property can be accessed using a snake_case counterpart.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset( string $name ): bool {
        $camelName = Util::snakeToCamel( $name );

        return property_exists( $this, $camelName );
    }

}
