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

namespace AlexaCRM\Nextgen\Twig;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Implements a proxy record object for the entity record fetcher for Twig templates.
 *
 * @property-read string $Value
 */
class FauxColumn {

    /**
     * @var string
     */
    public string $Name;

    /**
     * @var string
     */
    public string $Size;

    /**
     * @var string
     */
    public string $Url;

    /**
     * @var string
     */
    public string $Type;

    /**
     * @var mixed
     */
    protected $_value;

    /**
     * @param $value
     */
    public function __construct( $value ) {
        $this->_value = $value;
    }

    /**
     * @return string
     */
    public function __toString() {
        return (string)$this->_value;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function __get( $name ) {
        /* Allow readonly access to the $Value property. */
        if ( $name === 'Value' ) {
            return $this->_value;
        }

        return null;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function __isset( $name ) {
        $availableProperties = [ 'Value' ];

        if ( in_array( $name, $availableProperties ) ) {
            return true;
        }

        return false;
    }

}
