<?php
/*
                               (
                              (/(
                              (//(
                              (///(
                             (/////(
                             (//////(                          )
                            (////////(                        (/)
                            (////////(                       (///)
                           (//////////(                      (////)
                           (//////////(                     (//////)
                          (////////////(                    (///////)
                         (/////////////(                   (/////////)
                        (//////////////(                  (///////////)
                        (///////////////(                (/////////////)
                       (////////////////(               (//////////////)
                      (((((((((((((((((((              (((((((((((((((
                     (((((((((((((((((((              ((((((((((((((
                     (((((((((((((((((((            ((((((((((((((
                    ((((((((((((((((((((           (((((((((((((
                    ((((((((((((((((((((          ((((((((((((
                    (((((((((((((((((((         ((((((((((((
                    (((((((((((((((((((        ((((((((((
                    ((((((((((((((((((/      (((((((((
                    ((((((((((((((((((     ((((((((
                    (((((((((((((((((    (((((((
                   ((((((((((((((((((  (((((
                   #################  ##
                   ################  #
                  ################# ##
                 %################  ###
                 ###############(   ####
                ###############      ####
               ###############       ######
              %#############(        (#######
             %#############           #########
            ############(              ##########
           ###########                  #############
          #########                      ##############
        %######

        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Libraries;

use ReflectionException;
use ReflectionExtension;
use ReflectionFunction;

/**
 * Class Sodium
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Sodium
{

    /**
     * @throws ReflectionException
     * @throws \Exception
     */
    public static function libsodium2sodium ()
    {

        if ( extension_loaded( 'libsodium' ) ) {

            $sodium = new ReflectionExtension( 'libsodium' );

            foreach ( $sodium->getConstants() as $primaryName => $value ) {

                if ( stripos( $primaryName, 'SODIUM_' ) !== 0 ) {

                    $name = strtoupper( str_replace( '\\', '_', $primaryName ) );

                    if ( ! defined( $name ) ) {

                        define( $name, $value );

                    }

                }

            }

            foreach ( $sodium->getFunctions() as $primaryName => $launch ) {

                if ( stripos( $primaryName, 'sodium_' ) !== 0 ) {

                    static::createFunctionAlias( $launch, lcfirst( str_replace( '\\', '_', $primaryName ) ) );

                }

            }

        }
        else {
        	throw new \Exception('Sodium extension is required.');
		}

    }

    /**
     * Creates a function alias
     *
     * @param ReflectionFunction $functionReflection
     * @param string $aliasName
     * @return bool
     * @throws ReflectionException
     */
    private static function createFunctionAlias ( $functionReflection, $aliasName )
    {

        if ( ! function_exists( $aliasName ) ) {

            $functionName = $functionReflection->getName();

            if ( stripos( $functionName, '\\' ) !== 0 ) {

                $functionName = '\\'.$functionName;

            }

            $function = $aliasName.'(';
            $needComma = false;

            foreach ( $functionReflection->getParameters() as $param ) {

                if ( $needComma ) {

                    $function .= ',';

                }

                if ( $param->isPassedByReference() ) {

                    $function .= '&';

                }

                $function .= '$'.$param->getName();

                if ( $param->isDefaultValueAvailable() ) {

                    $val = $param->getDefaultValue();

                    if ( is_string( $val ) ) {
                        $val = "'$val'";
                    }

                    $function .= ' = ' . $val;

                }
                else if ( in_array( $functionName, [ '\Sodium\hex2bin', '\Sodium\memzero', ], true ) ) {

                    if ( $param->getPosition() === 1 ) {

                        if ( '\Sodium\memzero' === $functionName ) {

                            $function .= " = null";

                        }

                        if ( '\Sodium\hex2bin' === $functionName ) {

                            $function .= " = ''";

                        }

                    }

                }

                $needComma = true;
            }

            $function = 'function ' . $function . ')' . PHP_EOL .
                '{return call_user_func_array("' . $functionName . '", func_get_args());}';

            eval( $function );
            return true;

        }

        return false;

    }

}

