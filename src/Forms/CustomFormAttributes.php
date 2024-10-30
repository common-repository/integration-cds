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

use AlexaCRM\Nextgen\CreateFromArrayTrait;
use AlexaCRM\Nextgen\Twig\Util;
use AlexaCRM\Xrm\Entity;
use AlexaCRM\Xrm\EntityReference;

/**
 * Represents attributes available for a custom forms.
 */
class CustomFormAttributes {

    use CreateFromArrayTrait;

    /**
     * Tells that the form will create new records.
     */
    const MODE_CREATE = 'create';

    /**
     * Tells that the form will update existing record.
     */
    const MODE_UPDATE = 'update';

    /**
     * Entity name.
     *
     * @var string
     */
    public ?string $entity = null;

    /**
     * Processing mode.
     *
     * @var string
     */
    public ?string $mode = null;

    /**
     * Bound record.
     *
     * @var EntityReference|null
     */
    public ?EntityReference $record = null;

    /**
     * Whether reCAPTCHA validation is required.
     *
     * @var bool
     */
    public bool $recaptcha = false;

    /**
     * Contains redirect URL value.
     *
     * If not empty, redirection will be performed after a successful submission.
     *
     * @var string
     */
    public string $redirect = '';

    /**
     * Contains optional successful submission message.
     *
     * @var string
     */
    public string $message = '';

    /**
     * Contains optional success and failed submission messages.
     *
     * @var array|null
     */
    public ?array $messages = null;

    /**
     * Do not collapse the form after a successful submission.
     *
     * @var bool
     */
    public bool $keep = false;

    /**
     * Do not clean input values from the form inputs after a successful submission.
     *
     * @var bool
     */
    public bool $keepData = false;

    /**
     * Creates a new class instance from the given data.
     *
     * @param array $data
     *
     * @return static
     */
    public static function createFromArray( $data ) {
        $instance = new static();

        foreach ( $data as $key => $value ) {
            $normalKey = Util::snakeToCamel( $key );

            if ($normalKey === 'record'){
                /**
                 * Make sure CustomFormAttributes::$record is an EntityReference.
                 */
                if ( $value instanceof Entity ) {
                    $value = $value->ToEntityReference();
                } elseif ( is_string( $value ) && isset( $instance->entity ) ) {
                    $value = new EntityReference( $instance->entity, $value );
                } elseif ( !( $value instanceof EntityReference ) ) {
                    $value = null;
                }
            }

            if ($normalKey === 'recaptcha'){
                if ( !is_bool( $value ) ) {
                    $value = $value === 'true' || $value === '1' || $value === 1;
                }
            }

            $instance->{$normalKey} = $value;
        }

        return $instance;
    }

}
