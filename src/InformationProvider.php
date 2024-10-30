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

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use AlexaCRM\Xrm\Query\FetchExpression;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Provides information about system, plugin, etc.
 */
class InformationProvider {

    use SingletonTrait;

    /**
     * Link to the solution in AppSource.
     */
    public const SOLUTION_MARKETPLACE_URL = 'https://alexacrm.com/dataverse-solution/';

    /**
     * Internal identifier of the premium addon.
     */
    public const PREMIUM_ID = 'integration-cds-premium/integration-cds-premium.php';

    /**
     * Solution identifier.
     */
    private const SOLUTION_UNIQUENAME = 'WordPressIntegration';

    /**
     * Returns various system information.
     *
     * @return array
     */
    public function getSystemInformation(): array {
        global $wpdb, $wp_version;

        $info = [
            'web-server-name' => $_SERVER['SERVER_SOFTWARE'],
            'php-version' => PHP_VERSION,
            'php-memory-limit' => ini_get( 'memory_limit' ),
            'php-upload-max-filesize' => ini_get( 'upload_max_filesize' ),
            'php-post-max-size' => ini_get( 'post_max_size' ),
            'php-max-execution-time' => ini_get( 'max_execution_time' ),

            'php-allow-url-fopen' => (bool)ini_get( 'allow_url_fopen' ),
            'php-allow-url-include' => (bool)ini_get( 'allow_url_fopen' ),
            'php-display-errors' => (bool)ini_get( 'display_errors' ),

            'db-version' => $wpdb->db_version(),
            'db-charset' => $wpdb->charset,
            'db-collate' => $wpdb->collate,

            'session-save-path' => ini_get( 'session.save_path' ),
            'tmp-path' => ini_get( 'upload_tmp_dir' ),

            'mbstring-enabled' => extension_loaded( 'mbstring' ),

            'site-url' => get_site_url(),
            'site-domain' => $_SERVER['HTTP_HOST'],

            'wp-version' => $wp_version,
            'wp-table-prefix' => $wpdb->base_prefix,
        ];

        $info['curl-enabled'] = extension_loaded( 'curl' );

        if ( $info['curl-enabled'] ) {
            $info['curl-details'] = curl_version();

            $info['curl-details']['features'] = array_keys( array_filter(
                [
                    'http2' => CURL_VERSION_HTTP2,
                    'ipv6' => CURL_VERSION_IPV6,
                    'kerberos4' => CURL_VERSION_KERBEROS4,
                    'ssl' => CURL_VERSION_SSL,
                    'libz' => CURL_VERSION_LIBZ,
                ],
                function( $feature ) use ( $info ) {
                    return ( $info['curl-details']['features'] & $feature );
                }
            ) );
        }

        return $info;
    }

    /**
     * Returns various information about system resources usage and performance.
     *
     * @return array
     */
    public function getResourcesInformation(): array {
        $memoryUsage = $this->formatDiskSpace( memory_get_peak_usage(), 0 );
        $memoryTotal = ini_get( 'memory_limit' );

        $diskUsage = $diskTotal = __( 'Unknown', 'integration-cds' );
        if ( function_exists( 'disk_total_space' ) && function_exists( 'disk_free_space' ) ) {
            $diskUsage = $this->formatDiskSpace( disk_total_space( ICDS_DIR ) - disk_free_space( ICDS_DIR ) );
            $diskTotal = $this->formatDiskSpace( disk_total_space( ICDS_DIR ) );
        }

        return [
            'memory-usage' => $memoryUsage,
            'memory-total' => $memoryTotal,
            'disk-usage' => $diskUsage,
            'disk-total' => $diskTotal,
        ];
    }

    /**
     * Returns various information about plugin.
     *
     * @return array
     */
    public function getPluginInformation(): array {
        return [
            'plugin-title' => 'Dataverse Integration',
            'plugin-version' => ICDS_VERSION,
        ];
    }

    /**
     * Returns various information about solution.
     *
     * @return array
     */
    public function getSolutionInformation(): array {
        $pool = CacheProvider::instance()->providePool( '', HOUR_IN_SECONDS );
        $cache = $pool->getItem( 'solution-information' );
        if ( $cache->isHit() ) {
            return $cache->get();
        }

        $result = [
            'isInstalled' => $this->isSolutionInstalled(),
            'version' => '',
        ];

        if ( $result['isInstalled'] ) {
            $client = ConnectionService::instance()->getClient();
            try {
                $solutions = $client->RetrieveMultiple( new FetchExpression( /** @lang XML */ '
                    <fetch>
                        <entity name="solution">
                            <attribute name="friendlyname" />
                            <attribute name="uniquename" />
                            <attribute name="version" />
                            <filter>
                                <condition attribute="uniquename" operator="eq" value="WordPressIntegration" />
                            </filter>
                        </entity>
                    </fetch>
                ' ) );

                $result['version'] = $solutions->Entities[0]['version'];
            } catch ( \Exception $e ) {
            }
        }

        $pool->save( $cache->set( $result ) );

        return $result;
    }

    /**
     * Determines whether premium solution is installed in the Dataverse.
     *
     * Only say FALSE if the solution is definitely not installed.
     * In other cases return TRUE.
     *
     * @return bool
     */
    public function isSolutionInstalled(): bool {
        $pool = CacheProvider::instance()->providePool( '', HOUR_IN_SECONDS );
        $cache = $pool->getItem( 'is-solution-installed' );
        $logger = LoggerProvider::instance()->getLogger();
        if ( $cache->isHit() ) {
            $logger->debug( '[isHit]: Get is-solution-installed flag from the cache' );
            return $cache->get();
        }
        $logger->debug( '[isHit]: is-solution-installed flag was not found in cache' );

        $client = ConnectionService::instance()->getClient();
        if ( $client === null ) {
            return true;
        }

        $proxy = $client->getClient();
        try {
            $proxy->executeAction( 'alexacrm_WordPressRequest', [
                'Version' => '1.0.0.0',
                'Caller' => 'Dataverse Integration/' . ICDS_VERSION,
                'Site' => site_url(),
                'Name' => 'GetLicense',
                'Request' => '',
            ] );
        } catch ( BadResponseException $e ) {
            $resp = $e->getResponse();
            if ( $resp === null || $resp->getStatusCode() === 404 ) {
                $pool->save( $cache->set( false ) );

                return false;
            }

            $pool->save( $cache->set( true ) );

            return true;
        } catch ( \Exception $e ) {
            $pool->save( $cache->set( true ) );

            return true;
        }

        $pool->save( $cache->set( true ) );

        return true;
    }

    /**
     * Determines whether the premium addon is installed.
     *
     * @return bool
     */
    public function isPremiumInstalled(): bool {
        return AddonsManager::instance()->isInstalled( self::PREMIUM_ID );
    }

    /**
     * Determines whether the premium addon is activated.
     *
     * @return bool
     */
    public function isPremiumActive(): bool {
        return AddonsManager::instance()->isActive( self::PREMIUM_ID );
    }

    /**
     * Returns a list of errors caused by the premium addon if any.
     *
     * @return \WP_Error[]
     */
    public function getPremiumErrors(): array {
        if ( !$this->isPremiumActive() ) {
            return [];
        }

        $premuim = AddonsManager::instance()->getAddon( self::PREMIUM_ID );

        if ( $premuim->hasErrors() ) {
            return $premuim->getErrors();
        }

        return [];
    }

    /**
     * Determine whether the premium addon is operating.
     *
     * @return bool
     */
    public function isPremiumOperating(): bool {
        $addons = AddonsManager::instance()->getActiveAddons();

        if ( !array_key_exists( self::PREMIUM_ID, $addons ) ) {
            return false;
        }

        $premium = $addons[ self::PREMIUM_ID ];

        if ( !empty( $premium->extra ) ) {
            foreach ( $premium->extra as $info ) {
                if ( $info->status === "763010001" ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Transform disk space data in bytes to human-readable format.
     *
     * @param int|float $bytes
     * @param int $precision
     *
     * @return string
     */
    private function formatDiskSpace( $bytes, int $precision = 2 ): string {
        $units = [ '', 'K', 'M', 'G', 'T' ];

        $pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
        $pow = min( $pow, count( $units ) - 1 );
        $bytes /= 1024 ** $pow;

        return round( $bytes, $precision ) . $units[ $pow ];
    }
}
