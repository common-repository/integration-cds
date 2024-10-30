<?php

namespace AlexaCRM\Nextgen\Twig;

use Detection\MobileDetect;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MobileDetectExtension extends AbstractExtension {

    protected MobileDetect $detector;

    /**
     * Constructor
     */
    public function __construct() {
        $this->detector = new MobileDetect();
    }

    /**
     * Twig functions
     *
     * @return array
     */
    public function getFunctions(): array {
        $mobileDetectFunctions = get_class_methods( $this->detector );

        $functions = [];

        foreach ( $mobileDetectFunctions as $mobileDetectFunction ) {
            if ( !str_starts_with( $mobileDetectFunction, 'get' ) && !str_starts_with( $mobileDetectFunction, 'is' ) ) {
                continue;
            }

            $snakeFunctionName = $mobileDetectFunction;
            $functions[] = new TwigFunction( $snakeFunctionName, [ $this, $mobileDetectFunction ] );
        }

        foreach ( $this->getAvailableDevices() as $device => $fixedName ) {
            $methodName = 'is' . ucfirst($device);
            $functions[] = new TwigFunction( $methodName, [ $this, $methodName ] );
        }

        return $functions;
    }

    /**
     * Returns an array of all available devices
     *
     * @return array
     */
    public function getAvailableDevices(): array {
        $availableDevices = [];
        $rules = array_change_key_case( $this->detector->getRules() );

        foreach ( $rules as $device => $rule ) {
            $availableDevices[ $device ] = static::fromCamelCase( $device );
        }

        return $availableDevices;
    }

    /**
     * Pass through calls of undefined methods to the mobile detect library
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call( $name, $arguments ) {
        return call_user_func_array( [ $this->detector, $name ], $arguments );
    }

    /**
     * The extension name
     *
     * @return string
     */
    public function getName(): string {
        return 'mobile_detect.twig.extension';
    }

    /**
     * Converts a string to camel case
     *
     * @param $string
     *
     * @return array|string|string[]|null
     */
    protected static function toCamelCase( $string ) {
        return preg_replace( '~\s+~', '', lcfirst( ucwords( strtr( $string, '_', ' ' ) ) ) );
    }

    /**
     * Converts a string from camel case
     *
     * @param $string
     * @param string $separator
     *
     * @return string
     */
    protected static function fromCamelCase( $string, string $separator = '_' ): string {
        return strtolower( preg_replace( '/(?!^)[[:upper:]]+/', $separator . '$0', $string ) );
    }
}
