<?php
/**
 * Copyright 2018 AlexaCRM
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

class StorageHelper {

    /**
     * Name of the directory used for storage.
     */
    const STORAGE_DIR = 'integration-cds';

    protected static ?string $storagePath = null;

    /**
     * Returns the storage path.
     *
     * @return string
     */
    public static function getStoragePath(): string {
        if ( static::$storagePath !== null ) {
            return static::$storagePath;
        }

        $uploads = wp_upload_dir();
        static::$storagePath = $uploads['basedir'] . '/' . static::STORAGE_DIR;

        return static::$storagePath;
    }

    /**
     * Tells whether the storage is available.
     */
    public static function isStorageAvailable(): bool {
        $path = static::getStoragePath();

        return ( wp_mkdir_p( $path ) && wp_is_writable( $path ) );
    }

}
