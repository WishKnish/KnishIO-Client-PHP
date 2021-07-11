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

use Tuupola\Base58Proxy as Base;

/**
 * Class Base58Static
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Base58Static extends Base
{

    /**
     * @var array
     */
    public static $options = [
        'characters' => Base58::GMP,
        'check' => false,
        'version' => 0x00,
    ];

    /**
     * Encode given data to a base58 string
     *
     * @param string $data
     * @return string
     */
    public static function encode ( $data )
    {
        return ( new Base58( self::$options ) )
            ->encode( $data );
    }

    /**
     * Decode given base58 string back to data
     *
     * @param string $data
     * @param bool $integer
     * @return string|integer
     */
    public static function decode ( $data, $integer = false )
    {
        return ( new Base58( self::$options ) )
            ->decode( $data, $integer );
    }

    /**
     * Encode given integer to a base58 string
     *
     * @param integer $data
     * @return string
     */
    public static function encodeInteger ( $data )
    {
        return ( new Base58( self::$options ) )
            ->encodeInteger( $data );
    }

    /**
     * Decode given base58 string back to an integer
     *
     * @param string $data
     * @return integer
     */
    public static function decodeInteger ( $data )
    {
        return ( new Base58( self::$options ) )
            ->decodeInteger( $data );
    }

}
