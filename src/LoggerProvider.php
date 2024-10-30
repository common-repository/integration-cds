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

use AlexaCRM\WebAPI\OData\OnlineSettings;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provides loggers.
 */
class LoggerProvider {

    use SingletonTrait;

    private const FILENAME = 'integration-cds.log';

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * Effective log level.
     */
    protected ?int $logLevel = null;

    /**
     * Full path to log file.
     *
     * @var string
     */
    protected string $logPath;

    /**
     * @var string
     */
    protected string $filename = 'integration-cds.log';

    /**
     * @var string
     */
    protected string $filenameFormat = '{filename}-{date}-{uniqid}';

    /**
     * @var string
     */
    protected string $dateFormat = 'Y-m-d';

    /**
     * LoggerProvider constructor.
     */
    protected function __construct() {
        $this->logPath = StorageHelper::getStoragePath() . '/' . LoggerProvider::FILENAME;
        $this->logger = $this->createNewLogger( 'integration-cds' );

        if ( class_exists( 'QM' ) ) {
            // Hide logging infrastructure from the caller traces in Query Monitor.
            add_filter( 'qm/trace/ignore_class', function( $classes ) {
                $classes[ QueryMonitorLogHandler::class ] = true;
                $classes[ DatabaseLogHandler::class ] = true;
                $classes[ AbstractProcessingHandler::class ] = true;
                $classes[ Logger::class ] = true;

                return $classes;
            } );
        }
    }

    /**
     * Provides a logger instance.
     *
     * @return Logger
     */
    public function getLogger(): Logger {
        return $this->logger;
    }

    /**
     * Returns the effective log level used to determine logging verbosity.
     *
     * The plugin provides 3 options to configure the logging verbosity,
     * in order of priority, the latter overrides the former:
     *  - Verbosity configured via Settings UI,
     *  - `ICDS_LOG_LEVEL` constant, if defined,
     *  - `integration-cds/logging/level` filter, if hooked to.
     *
     * @return int
     * @see \Monolog\Logger
     */
    public function getLogLevel(): int {
        if ( $this->logLevel !== null ) {
            return $this->logLevel;
        }

        $icdsLogLevel = AdvancedSettingsProvider::instance( 'ICDS_LOG_LEVEL' );

        if ( $icdsLogLevel->isSet() ) {
            $logLevel = $icdsLogLevel->getValue();
        } else {
            /** @var LoggingSettings $loggingSettings */
            $loggingSettings = SettingsProvider::instance()->getSettings( 'logging' );
            $logLevel = $loggingSettings->verbosity;
        }

        /**
         * Filters the effective logging verbosity.
         *
         * @param int $logLevel
         */
        $this->logLevel = apply_filters( 'integration-cds/logging/level', $logLevel );

        return $this->logLevel;
    }

    /**
     * Returns certain number of recent log records.
     *
     * @param int $records Number of records to return.
     * @param null $filters
     *
     * @return LogRecord[]
     */
    public function getRecentLogRecords( int $records = 1, $filters = null ): array {
        $logHandlers = $this->logger->getHandlers();
        $result = [];

        if ( empty( $logHandlers ) ) {
            return $result;
        }

        $recentHandler = $logHandlers[0];

        // Our deduplication handler contains the rotating log handler. Bail if it does not.
        if ( !( $recentHandler instanceof DeduplicationHandler ) ) {
            return $result;
        }

        $dbHandlers = array_filter( $logHandlers, function( $handler ) {
            return $handler instanceof DatabaseLogHandler;
        } );

        if ( count( $dbHandlers ) > 0 ) {
            if ( !$logs = get_transient( DatabaseLogHandler::STORE_IN_DB_KEY ) ) {
                return $result;
            }

            //TODO: implement filters
            return array_slice( $logs, -$records, $records );
        }

        $logFiles = glob( $this->getGlobPattern() );
        rsort( $logFiles );

        foreach ( $logFiles as $file ) {
            $lines = $this->tailFile( $file, $records );
            rsort( $lines );

            foreach ( $lines as $line ) {
                $parsedLine = $this->parseLogRecord( $line );

                if ( $parsedLine !== null ) {
                    if ( !empty( $filters ) ) {
                        if ( LogFilter::filter( $parsedLine, $filters ) ) {
                            $result[ $parsedLine->getHash() ] = $parsedLine;
                        }
                    } else {
                        $result[ $parsedLine->getHash() ] = $parsedLine;
                    }
                }

                $records--;

                if ( $records === 0 ) {
                    break 2;
                }
            }
        }

        return $result;
    }

    /**
     * Returns a new logger instance with the specified name.
     *
     * @param string $name
     *
     * @return Logger
     */
    public function getNewLogger( string $name ): Logger {
        /*
         * Conflicting plugins may override our Monolog version with a pre-1.18 package
         * which lacks the \Monolog\Logger::withName() method.
         */
        if ( method_exists( $this->logger, 'withName' ) ) {
            return $this->logger->withName( $name );
        }

        return $this->createNewLogger( $name );
    }

    /**
     * Creates a new logger instance.
     *
     * @param string $name \Monolog\Logger name.
     *
     * @return Logger
     */
    protected function createNewLogger( string $name ): Logger {
        $duplicateInQm = AdvancedSettingsProvider::instance( 'ICDS_QM_LOGS' )->isTrue();
        $storeLogsInDb = AdvancedSettingsProvider::instance( 'ICDS_DB_LOGS' )->isTrue();
        $logger = new Logger( $name );
        $logLevel = $this->getLogLevel();

        if ( class_exists( 'QM' ) && $duplicateInQm ) {
            $logger->pushHandler( new QueryMonitorLogHandler() );
        }

        if ( $storeLogsInDb ) {
            $logger->pushHandler( new DatabaseLogHandler() );
        }

        $handler = new NullHandler( $logLevel );

        if ( StorageHelper::isStorageAvailable() ) {
            $fileHandler = new RotatingFileHandler( $this->logPath, 3, $logLevel );

            // EncryptionService::DEFAULT_ENCRYPTION_KEY is hardcoded here to increase performance
            $uniqId = 'u' . hash( 'crc32b', date( $this->dateFormat ) . 'abcdefghijklmnopqrstuvwxyz123456' );
            $filenameFormatPrepared = str_replace( '{uniqid}', $uniqId, $this->filenameFormat );

            $fileHandler->setFilenameFormat( $filenameFormatPrepared, $this->dateFormat );

            if ( !file_exists( $fileHandler->getUrl() ) || is_writable( $fileHandler->getUrl() ) ) {
                $handler = new DeduplicationHandler( $fileHandler, null, Logger::NOTICE );
            } else {
                $logger->warning( 'File logs are disabled due to file write restriction.', [
                    'path' => $fileHandler->getUrl(),
                ] );
            }
        }

        $logger->pushHandler( $handler );

        $logger->pushProcessor( function( $record ) {
            if ( isset( $record['context']['settings'] ) && $record['context']['settings'] instanceof OnlineSettings ) {
                /** @var OnlineSettings $recordSettings */
                $recordSettings = clone $record['context']['settings'];
                $recordSettings->applicationSecret = '';

                $record['context']['settings'] = $recordSettings;
            }

            return $record;
        } );

        return $logger;
    }

    /**
     * Returns pattern to match all available log files.
     *
     * @return string
     */
    protected function getGlobPattern(): string {
        $fileInfo = pathinfo( $this->logPath );
        $glob = str_replace(
            [ '{filename}', '{date}', '{uniqid}' ],
            [
                $fileInfo['filename'],
                str_replace(
                    [ 'Y', 'y', 'm', 'd' ],
                    [ '[0-9][0-9][0-9][0-9]', '[0-9][0-9]', '[0-9][0-9]', '[0-9][0-9]' ],
                    $this->dateFormat
                ),
                'u' . str_repeat( '?', 8 ),
            ],
            ( $fileInfo['dirname'] ?? '' ) . '/' . $this->filenameFormat
        );
        if ( !empty( $fileInfo['extension'] ) ) {
            $glob .= '.' . $fileInfo['extension'];
        }

        return $glob;
    }

    /**
     * Reads a file from the end and retrieve certain number of lines.
     *
     * @link https://gist.github.com/lorenzos/1711e81a9162320fde20
     *
     * @param string $filename Name of file to retrieve lines from.
     * @param int $lines Number of lines to retrieve.
     * @param bool $adaptive Use dynamic buffer length according to the number of lines to retrieve.
     *
     * @return string[] Array of lines from the file.
     */
    protected function tailFile( string $filename, int $lines = 1, bool $adaptive = true ): array {
        $f = @fopen( $filename, "rb" );

        if ( $f === false ) {
            return [];
        }

        // Sets buffer size, according to the number of lines to retrieve.
        if ( !$adaptive ) {
            $buffer = 4096;
        } else if ( $lines < 10 ) {
            $buffer = ( $lines < 2 ? 64 : ( 512 ) );
        } else {
            $buffer = ( $lines < 2 ? 64 : ( 4096 ) );
        }

        // Jump to last character.
        fseek( $f, -1, SEEK_END );

        // Read last character and adjust line number if necessary.
        if ( fread( $f, 1 ) !== "\n" ) {
            --$lines;
        }

        $output = '';

        while ( ftell( $f ) > 0 && $lines >= 0 ) {
            // Figure out how far back we should jump.
            $seek = min( ftell( $f ), $buffer );
            fseek( $f, -$seek, SEEK_CUR );

            $output = ( $chunk = fread( $f, $seek ) ) . $output;

            // Jump back to where we started reading.
            fseek( $f, -mb_strlen( $chunk, '8bit' ), SEEK_CUR );
            $lines -= substr_count( $chunk, "\n" );
        }

        fclose( $f );

        // Remove extra lines in case we have read too many lines to buffer.
        while ( $lines++ < 0 ) {
            $output = substr( $output, strpos( $output, "\n" ) + 1 );
        }

        return explode( "\n", trim( $output ) );
    }

    /**
     * Parses log records.
     *
     * @param string $line Line from log file.
     *
     * @return LogRecord|null
     */
    protected function parseLogRecord( string $line ): ?LogRecord {
        if ( empty( $line ) ) {
            return null;
        }

        [ $splittedLine, $extra ] = $this->extractRecordMeta( $line );
        [ $splittedLine, $context ] = $this->extractRecordMeta( $splittedLine );

        preg_match(
            '%\[(?<datetime>.*?)] (?<name>.*?)\.(?<level>.*?): (?<message>.*)%',
            $splittedLine,
            $parsedLine
        );

        $record = new LogRecord();
        $record->name = $parsedLine['name'] ?? '';
        $record->datetime = $parsedLine['datetime'] ?? '';
        $record->level = $parsedLine['level'] ?? '';
        $record->message = $parsedLine['message'] ?? '';
        $record->context = json_encode(
            json_decode( $context ),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );
        $record->extra = $extra;

        return $record;
    }

    /**
     * Extracts metadata from the log record such as 'context' and 'extra'.
     *
     * @see https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md#adding-extra-data-in-the-records
     *
     * @param string $line Line from log file.
     *
     * @return string[] Array containing the line separated into the two parts - a whole line without searched metadata and extracted metadata.
     */
    protected function extractRecordMeta( string $line ): array {
        $normalLine = trim( $line );
        $linePos = -1;
        $lastChar = substr( $normalLine, $linePos, 1 );
        $nestedBrackets = 0;

        if ( $lastChar === ']' ) {
            $openChar = ']';
            $closeChar = '[';
        } elseif ( $lastChar === '}' ) {
            $openChar = '}';
            $closeChar = '{';
        } else {
            return [ $line, '' ];
        }

        while ( abs( $linePos ) < strlen( $line ) ) {
            $linePos--;
            $currChar = substr( $normalLine, $linePos, 1 );
            if ( $currChar === $openChar ) {
                $nestedBrackets++;
            } elseif ( $currChar === $closeChar && $nestedBrackets > 0 ) {
                $nestedBrackets--;
            } elseif ( $currChar === $closeChar & $nestedBrackets === 0 ) {
                break;
            }
        }
        $linePos--;

        return [ substr( $line, 0, $linePos ), substr( $line, $linePos ) ];
    }

}
