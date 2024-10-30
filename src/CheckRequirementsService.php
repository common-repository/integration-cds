<?php
/**
 * Copyright 2022 AlexaCRM
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

/**
 * This class checks if the client environment meets the plugin's requirements.
 */
class CheckRequirementsService {

    use SingletonTrait;

    /**
     * Constant representation for the mandatory key extensions
     */
    public const MANDATORY = 'mandatory';

    /**
     * Constant representation for the recommended key extensions
     */
    public const RECOMMENDED = 'recommended';

    /**
     * Constant representation of the list of recommended extensions
     */
    public const RECOMMENDED_EXTENSIONS = [
        'mbstring',
    ];

    /**
     * Constant representation of the list of mandatory extensions
     */
    public const MANDATORY_EXTENSIONS = [
        'curl',
        'dom',
        'intl',
    ];

    /**
     * @var array
     */
    private array $errorMessages = [];

    /**
     * @var array
     */
    private array $missingList = [];

    /**
     * CheckRequirementsService constructor.
     */
    public function __construct() {
        $this->checkTempPathIsWritable();
        $this->checkExtensions();
    }

    /**
     * Check is temp path is writable
     */
    private function checkTempPathIsWritable(): void {
        $tempPath = sys_get_temp_dir();
        if ( !is_writable( $tempPath ) ) {
            $this->errorMessages[ self::MANDATORY ][] = "Your temporary dir ($tempPath) must be writable.";
        }
    }

    /**
     * Check if extensions are installed
     */
    private function checkExtensions(): void {
        $loadedExtensions = get_loaded_extensions();
        foreach ( self::RECOMMENDED_EXTENSIONS as $ext ) {
            if ( !in_array( $ext, $loadedExtensions ) ) {
                $this->missingList[ self::RECOMMENDED ][] = $ext;
                $this->errorMessages[ self::RECOMMENDED ][] = "We have found that the '$ext' PHP extension is not installed. We recommend to install it for better experience.";
            }
        }
        foreach ( self::MANDATORY_EXTENSIONS as $ext ) {
            if ( !in_array( $ext, $loadedExtensions ) ) {
                $this->missingList[ self::MANDATORY ][] = $ext;
                $this->errorMessages[ self::MANDATORY ][] = "We have found that the '$ext' PHP extension is not installed. You must have this extension to be installed in order the Dataverse Integration plugin to work properly.";
            }
        }
    }

    /**
     * @return bool
     */
    public function hasMandatory(): bool {
        return isset( $this->errorMessages[ self::MANDATORY ] ) && count( $this->errorMessages[ self::MANDATORY ] );
    }

    /**
     * @return bool
     */
    public function hasRecommended(): bool {
        return isset( $this->errorMessages[ self::RECOMMENDED ] ) && count( $this->errorMessages[ self::RECOMMENDED ] );
    }

    /**
     * Print errors
     */
    public function printMandatoryErrors(): void {
        if ( $this->hasMandatory() ) {
            foreach ( $this->errorMessages[ self::MANDATORY ] as $error ) {
                echo "<div class='notice notice-error'>
                        <p>
                            $error
                        </p>
                      </div>";
            }
        }
    }

    /**
     * @return array
     */
    public function getErrors(): array {
        return $this->errorMessages;
    }

    /**
     * @return array
     */
    public function getMissingMandatory(): array {
        return $this->missingList[ self::MANDATORY ];
    }
}
