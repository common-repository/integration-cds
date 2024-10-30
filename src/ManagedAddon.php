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
 * Represents an addon to the plugin provided by Dataverse installation and centrally managed.
 */
class ManagedAddon implements AddonInterface {

    use CreateFromArrayTrait;

    /**
     * Unique name of the addon.
     *
     * @var string
     */
    protected string $name;

    /**
     * Display name.
     *
     * @var string
     */
    public string $title;

    /**
     * @var string
     */
    public string $description;

    /**
     * @var string
     */
    public string $version;

    /**
     * Url for downloading.
     *
     * @var string
     */
    public string $url;

    /**
     * List of addons names which the addon depends on.
     *
     * @var string[]
     */
    public array $dependencies;

    /**
     * Determines wherether addon can be installed as a WordPress plugin.
     *
     * @var bool
     */
    public bool $isWpPlugin;

    /**
     * List of previous versions available for download, in form [ 'version_tag' => 'packageUrl' ].
     *
     * @var array
     */
    public array $versions;

    /**
     * Url of the addon manifest file if any.
     *
     * @var string
     */
    public string $manifest;

    public $changelog;

    /**
     * Returns unique addon name.
     *
     * @return string
     */
    public function getName(): string{
        return $this->name;
    }

    /**
     * Creates a new class instance from the given data.
     *
     * @param array $data
     *
     * @return static
     */
    public static function createFromArray( array $data ){
        $instance = new static();

        foreach ( $data as $key => $value ) {
            $normalKey = $key;

            if ($key === 'packageUrl'){
                $normalKey = 'url';
            }

            $instance->{$normalKey} = $value;
        }

        if ( !isset( $instance->isWpPlugin ) ) {
            $instance->isWpPlugin = true;
        }

        return $instance;
    }

}
