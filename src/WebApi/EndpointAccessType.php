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

namespace AlexaCRM\Nextgen\WebApi;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Indicates the type of network access an endpoint has.
 *
 * @see https://docs.microsoft.com/en-us/dynamics365/customer-engagement/web-api/endpointaccesstype?view=dynamics-ce-odata-9
 */
class EndpointAccessType {

    /**
     * Default access. The actual access is determined by the endpoint URL.
     */
    const DEFAULT = "Microsoft.Dynamics.CRM.EndpointAccessType'Default'";

    /**
     * Internet access.
     */
    const INTERNET = "Microsoft.Dynamics.CRM.EndpointAccessType'Internet'";

    /**
     * Intranet access.
     */
    const INTRANET = "Microsoft.Dynamics.CRM.EndpointAccessType'Intranet'";

}
