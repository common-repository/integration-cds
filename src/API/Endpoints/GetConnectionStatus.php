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

namespace AlexaCRM\Nextgen\API\Endpoints;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Nextgen\API\AdministrativeEndpoint;
use AlexaCRM\Nextgen\API\BadRequestResponse;
use AlexaCRM\Nextgen\ConnectionService;
use AlexaCRM\Nextgen\ConnectionSettings;
use AlexaCRM\Nextgen\InformationProvider;
use AlexaCRM\Nextgen\SettingsProvider;
use AlexaCRM\Nextgen\WebApi\EndpointAccessType;
use AlexaCRM\Xrm\ColumnSet;
use AlexaCRM\Xrm\Query\FetchExpression;

/**
 * Provides an endpoint to check Dataverse connection status
 */
class GetConnectionStatus extends AdministrativeEndpoint {

    /**
     * @var string
     */
    public string $name = 'connection_status';

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_Error|\WP_REST_Response
     */
    public function respond( \WP_REST_Request $request ) {
        $webapi = ConnectionService::instance()->getClient();
        if ( $webapi === null ) {
            return new \WP_REST_Response( [ 'status' => false, 'isSolutionInstalled' => true, ] );
        }

        try {
            $userInfo = $webapi->getClient()->executeFunction( 'WhoAmI' );

            $userDetails = $webapi->Retrieve(
                'systemuser',
                $userInfo->UserId,
                new ColumnSet( [ 'fullname' ] )
            );

            $userRolesCollection = $webapi->RetrieveMultiple( new FetchExpression( /** @lang XML */ "
                <fetch>
                    <entity name='role'>
                        <attribute name='name' />
                        <attribute name='roleid' />
                        <link-entity name='systemuserroles' from='roleid' to='roleid' visible='false' intersect='true'>
                            <link-entity name='systemuser' from='systemuserid' to='systemuserid' alias='aa'>
                                <filter type='and'>
                                    <condition attribute='systemuserid' operator='eq' value='{$userInfo->UserId}' />
                                </filter>
                            </link-entity>
                        </link-entity>
                    </entity>
                </fetch>
            " ) );

            $userRoles = array_map( function ( $entity ) {
                return $entity->GetAttributeValue( 'name' );
            }, $userRolesCollection->Entities );

            /** @var object $organizationInfo */
            $organizationInfo = $webapi->getClient()->executeFunction(
                'RetrieveCurrentOrganization',
                [ 'AccessType' => EndpointAccessType::DEFAULT ]
            );

            /** @var ConnectionSettings $connectionSettings */
            $connectionSettings = SettingsProvider::instance()->getSettings( 'connection' );

        } catch ( \Exception $e ) {
            return new BadRequestResponse( 1, $e->getMessage() );
        }

        return new \WP_REST_Response( [
            'status' => true,
            'isSolutionInstalled' => InformationProvider::instance()->isSolutionInstalled(),
            'solutionDetails' => InformationProvider::instance()->getSolutionInformation(),
            'user_id' => $userInfo->UserId,
            'user_name' => $userDetails->GetAttributeValue( 'fullname' ),
            'user_roles' => implode( ',', $userRoles ),
            'organization_name' => $organizationInfo->Detail->FriendlyName,
            'organization_version' => $organizationInfo->Detail->OrganizationVersion,
            'organization_url' => $connectionSettings->instanceURI,
        ] );

    }
}
