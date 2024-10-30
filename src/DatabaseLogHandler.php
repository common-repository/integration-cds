<?php
/**
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

namespace AlexaCRM\Nextgen;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Provides a Monolog handler that sends logs to the WordPress Query Monitor.
 */
class DatabaseLogHandler extends AbstractProcessingHandler {

    /**
     * Map of log levels. Monolog integer levels are mapped to Query Monitor string level values.
     *
     * @var string[]
     */
    private static array $levelMap = [
        Logger::DEBUG => 'debug',
        Logger::INFO => 'info',
        Logger::NOTICE => 'notice',
        Logger::WARNING => 'warning',
        Logger::ERROR => 'error',
        Logger::CRITICAL => 'critical',
        Logger::ALERT => 'alert',
        Logger::EMERGENCY => 'emergency',
    ];

    /**
     * Represents key of the option for the stored logs in DB
     */
    public const STORE_IN_DB_KEY = 'icds_logs';

    /**
     * Expiration time in seconds
     */
    public const STORE_IN_DB_EXPIRATION = 600;

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @param array $record
     *
     * @return void
     */
    protected function write( array $record ): void {
        $currentLevel = LoggerProvider::instance()->getLogLevel();

        if ( $currentLevel > $record['level'] ) {
            return;
        }

        $storeLog = (object)[
            'level' => strtoupper( self::$levelMap[ $record['level'] ] ),
            'message' => $record['message'],
            'context' => json_encode( $record['context'], JSON_PRETTY_PRINT ),
            'datetime' => $record['datetime'],
        ];

        $logs = get_transient( self::STORE_IN_DB_KEY ) ?: [];
        $logs[] = $storeLog;

        $expTime = AdvancedSettingsProvider::instance( 'ICDS_DB_LOGS_EXPIRATION' )->isSet() ? AdvancedSettingsProvider::instance( 'ICDS_DB_LOGS_EXPIRATION' )->getValue() : self::STORE_IN_DB_EXPIRATION;
        set_transient( self::STORE_IN_DB_KEY, $logs, $expTime );
    }
}
