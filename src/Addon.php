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

/**
 * Represents an addon to the plugin.
 */
class Addon implements AddonInterface {

    use CreateFromArrayTrait;

    const STATUS_AVAILABLE = 'available';

    const STATUS_INSTALLED = 'installed';

    const STATUS_ACTIVE = 'active';

    /**
     * Internal name.
     */
    protected string $name;

    /**
     * Public info to be shown to users.
     */
    public string $title;

    public string $description;

    public string $version;

    /**
     * Extra information about addon if any.
     *
     * @var AddonInformation[]
     */
    public array $extra = [];

    /**
     * Addon status.
     *
     * @var string
     */
    public string $status;

    /**
     * Relative path to addon to be used in urls.
     */
    protected string $addon_url;

    /**
     * Absolute path to addon.
     */
    protected string $addon_dir;

    /**
     * Addon constructor.
     *
     * @param string $filename The filename of the plugin (__FILE__).
     */
    public function __construct( string $filename ){
        $this->name = plugin_basename( $filename );
        $this->status = self::STATUS_ACTIVE;

        $this->addon_url = plugin_dir_url( $filename );
        $this->addon_dir = dirname( $filename );
    }

    /**
     * Returns unique addon name.
     *
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * Returns addon status.
     *
     * @return string
     */
    public function getStatus(): string{
        return $this->status;
    }

    /**
     * Sets addon status.
     *
     * @param string $status
     *
     * @return void
     */
    public function setStatus( string $status ){
        if ( !in_array( $status, [ self::STATUS_AVAILABLE, self::STATUS_INSTALLED, self::STATUS_ACTIVE ] ) ) {
            return;
        }

        $this->status = $status;
    }

    /**
     * Determines whether addon causes any errors during operation.
     *
     * @return bool
     */
    public function hasErrors(): bool{
        foreach ( $this->extra as $info ) {
            if ( $info->name == AddonInformation::ERROR_KEY ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a list of errors caused by the addon.
     *
     * @return \WP_Error[]
     */
    public function getErrors(): array{
        $result = [];

        foreach ( $this->extra as $info ) {
            if ( $info->name == AddonInformation::ERROR_KEY ) {
                $errorCode = $info->status !== null ? $info->status : "1";
                $result[] = new \WP_Error( $errorCode, $info->value );
            }
        }

        return $result;
    }

    /**
     * Creates a new class instance from the given data.
     *
     * @param array $data
     *
     * @return static
     */
    public static function createFromArray( array $data ){
        if ( isset( $data['name'] ) ) {
            $instance = new static( $data['name'] );
        }

        if ( !isset( $instance ) ) {
            return null;
        }

        foreach ( $data as $key => $value ) {
            if ( strpos( $key, "\0" ) !== false ) {
                continue;
            }

            if ( !property_exists( static::class, $key ) ) {
                continue;
            }

            $instance->{$key} = $value;
        }

        return $instance;
    }

}
