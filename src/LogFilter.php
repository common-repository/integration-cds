<?php
/**
 * Copyright 2023 AlexaCRM
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

/**
 * Provides filtering capabilities for LogRecord object by specified conditions.
 */
class LogFilter {

    /**
     * Filter type level
     */
    public const LEVEL_TYPE = 'level';

    /**
     * Filter type datetime
     */
    public const DATETIME_TYPE = 'datetime';

    /**
     * 'Between' option for 'datetime' filter
     */
    public const DATETIME_TYPE_BETWEEN = 'between';

    /**
     * 'More' option for 'datetime' filter
     */
    public const DATETIME_TYPE_MORE = '>';

    /**
     * 'Less' option for 'datetime' filter
     */
    public const DATETIME_TYPE_LESS = '<';

    /**
     * Wether provided logs matches given filters.
     *
     * @param LogRecord $log
     * @param array $filters
     *
     * @return bool
     */
    public static function filter( LogRecord $log, array $filters = [] ): bool {
        $pass = false;
        if ( !empty( $filters ) ) {
            foreach ( $filters as $type => $options ) {
                if ( $type === self::LEVEL_TYPE ) {
                    $pass = self::filterByLevel( $log, $options );
                } elseif ( $type === self::DATETIME_TYPE && $pass === true ) {
                    $pass = self::filterByDatetime( $log, $options );
                }
            }
        }

        return $pass;
    }

    /**
     * Wether log level matches filter options.
     *
     * @param LogRecord $log
     * @param $options
     *
     * @return bool
     */
    private static function filterByLevel( LogRecord $log, $options ): bool {
        $pass = false;
        if ( is_array( $options ) ) {
            if ( in_array( $log->level, $options ) ) {
                $pass = true;
            }
        } else {
            if ( $log->level === $options ) {
                $pass = true;
            }
        }

        return $pass;
    }

    /**
     * Wether log datetime matches filter options.
     *
     * @param LogRecord $log
     * @param $options
     *
     * @return bool
     */
    private static function filterByDatetime( LogRecord $log, $options ): bool {
        $pass = false;
        if ( is_array( $options ) && !empty( $options ) ) {
            $dateTypeFilter = array_key_first( $options );
            // Should be 3 items in options array [type, more than timestamp, less than timestamp]
            if ( $options[ $dateTypeFilter ] === self::DATETIME_TYPE_BETWEEN && count( $options ) === 3 ) {
                // Log time
                $logTime = strtotime( $log->datetime );

                if ( $logTime >= $options[1] && $logTime <= $options[2] ) {
                    $pass = true;
                }
            } elseif ( $options[ $dateTypeFilter ] === self::DATETIME_TYPE_MORE ) {
                // TODO: implement this case (provided time more than log time)
            } elseif ( $options[ $dateTypeFilter ] === self::DATETIME_TYPE_LESS ) {
                // TODO: implement this case (provided time less than log time)
            }
        }

        return $pass;
    }
}
