<?php
/*
 * Copyright 2020 AlexaCRM
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

namespace AlexaCRM\Nextgen\API\EndpointsV2;

use AlexaCRM\Nextgen\AdvancedSettingsProvider;
use AlexaCRM\Nextgen\API\ServerErrorResponse;
use AlexaCRM\Nextgen\DatabaseLogHandler;
use AlexaCRM\Nextgen\StorageHelper;

/**
 * Updates log verbosity settings.
 */
class DownloadLogs extends AdministrativeEndpoint {

    public string $name = 'logs';

    public array $methods = [ 'GET' ];

    /**
     * Responds to a WP REST request.
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response|\WP_Error
     */
    public function respond( \WP_REST_Request $request ) {
        $storagePath = StorageHelper::getStoragePath();
        $logFiles = glob( $storagePath . '/*.log' );

        if ( AdvancedSettingsProvider::instance( 'ICDS_DB_LOGS' )->isTrue() ) {
            $logs = get_transient( DatabaseLogHandler::STORE_IN_DB_KEY );
            if ( !empty( $logs ) ) {
                return new \WP_REST_Response( [
                    'logs' => $logs,
                ] );
            } else {
                return new ServerErrorResponse();
            }
        }

        if ( empty( $logFiles ) ) {
            return new ServerErrorResponse();
        }

        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/octet-stream' );

        if ( class_exists( \ZipArchive::class ) && ( $zipPath = tempnam( sys_get_temp_dir(), 'icds' ) ) ) {
            $zip = new \ZipArchive();
            $zip->open( $zipPath, \ZipArchive::OVERWRITE );
            $zip->addGlob( $storagePath . '/*.log', 0, [ 'remove_all_path' => true ] );
            $zip->close();

            $date = date( 'YmdHi' );
            header( "Content-Disposition: attachment; filename=icds_logs_{$date}.zip" );

            readfile( $zipPath );

            unlink( $zipPath );

            die;
        }

        rsort( $logFiles );
        $logPath = array_shift( $logFiles );

        $filename = basename( $logPath );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        readfile( $logPath );

        die;
    }
}
