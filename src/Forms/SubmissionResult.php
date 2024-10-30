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

use AlexaCRM\Xrm\EntityReference;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Represents a result of form submission.
 */
class SubmissionResult {

    /**
     * Whether the submission was successful.
     */
    public bool $isSuccessful;

    /**
     * Regarding entity reference, e.g. newly created or updated row.
     *
     * The field is usually employed at front-end to provide redirection with GUID placeholder substitution,
     * e.g. `redirect="/after-applying?id=%s"`.
     */
    public ?EntityReference $reference = null;

    /**
     * Exception which called a failure.
     */
    public ?\Exception $exception = null;

    /**
     * Human-readable error message.
     *
     * @var string
     */
    public ?string $errorMessage = null;

    /**
     * Collection of required attributes which were not filled in.
     *
     * @var array
     */
    public array $trippedAttributes = [];

    /**
     * SubmissionResult constructor.
     *
     * @param bool $isSuccessful
     */
    public function __construct( bool $isSuccessful ) {
        $this->isSuccessful = $isSuccessful;
    }

}
