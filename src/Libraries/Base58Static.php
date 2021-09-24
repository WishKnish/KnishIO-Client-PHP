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
class Base58Static extends Base {

  /**
   * @var array
   */
  public static $options = [ 'characters' => Base58::GMP, 'check' => false, 'version' => 0x00, ];

  /**
   * Encode given data to a base58 string
   *
   * @param string $data
   *
   * @return string
   */
  public static function encode ( $data ): string {
    return ( new Base58( self::$options ) )->encode( $data );
  }

  /**
   * Decode given base58 string back to data
   *
   * @param string $data
   *
   * @return string
   */
  public static function decode ( $data, $integer = false ): string {
    return ( new Base58( self::$options ) )->decode( $data );
  }

  /**
   * Encode given integer to a base58 string
   *
   * @param integer $data
   *
   * @return string
   */
  public static function encodeInteger ( $data ): string {
    return ( new Base58( self::$options ) )->encodeInteger( $data );
  }

  /**
   * Decode given base58 string back to an integer
   *
   * @param string $data
   *
   * @return integer
   */
  public static function decodeInteger ( $data ): int {
    return ( new Base58( self::$options ) )->decodeInteger( $data );
  }

}
