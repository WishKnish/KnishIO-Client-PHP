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

use desktopd\SHA3\Sponge as SHA3;
use Exception;
use ReflectionException;
use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;

/**
 * Class Crypto
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Crypto {

  /**
   * @var string
   */
  private static $characters = Base58::GMP;

  /**
   * Generates a secret based on an optional seed
   *
   * @param null $seed
   * @param int $length
   *
   * @return string
   * @throws Exception
   */
  public static function generateSecret ( $seed = null, $length = null ) {
    $length = default_if_null( $length, 2048 );

    return in_array( $seed, [ null, '' ], true ) ? Strings::randomString( $length ) : bin2hex( Shake256::hash( $seed, $length / 4 ) );
  }

  /**
   * @param string|null $molecularHash
   * @param int|null $index
   *
   * @return string
   * @throws Exception
   */
  public static function generateBatchId ( ?string $molecularHash = null, ?int $index = null ) {

    if ( !in_array( null, [ $molecularHash, $index ], true ) ) {
      return static::generateBundleHash( $molecularHash . $index );
    }

    return Strings::randomString( 64 );
  }

  /**
   * Hashes the user secret to produce a bundle hash
   *
   * @param string $secret
   *
   * @return string
   * @throws Exception
   */
  public static function generateBundleHash ( $secret ) {

    return bin2hex( Shake256::hash( $secret, 32 ) );

  }

  /**
   * Encrypts the given message or data with the recipient's public key
   *
   * @param array|object $message
   * @param string $key
   *
   * @return string|null
   * @throws Exception|ReflectionException
   */
  public static function encryptMessage ( $message, $key ) {

    return ( new Soda( static::$characters ) )->encrypt( $message, $key );

  }

  /**
   * Uses the given private key to decrypt an encrypted message
   *
   * @param string $decrypted
   * @param string $privateKey
   * @param string $publicKey
   *
   * @return array|null
   * @throws ReflectionException
   */
  public static function decryptMessage ( $decrypted, $privateKey, $publicKey ) {

    return ( new Soda( static::$characters ) )->decrypt( $decrypted, $privateKey, $publicKey );

  }

  /**
   * Derives a private key for encrypting data with the given key
   *
   * @param string|null $key
   *
   * @return string|null
   * @throws Exception|ReflectionException
   */
  public static function generateEncPrivateKey ( $key = null ) {

    return ( new Soda( static::$characters ) )->generatePrivateKey( $key );

  }

  /**
   * Derives a public key for encrypting data for this wallet's consumption
   *
   * @param string $key
   *
   * @return string|null
   * @throws ReflectionException
   */
  public static function generateEncPublicKey ( $key ) {

    return ( new Soda( static::$characters ) )->generatePublicKey( $key );

  }

  /**
   * @param string $characters
   */
  public static function setCharacters ( $characters ) {

    $constant = Base58::class . '::' . $characters;

    static::$characters = defined( $constant ) ? constant( $constant ) : static::$characters;

  }

  /**
   * @return string
   */
  public static function getCharacters () {

    return static::$characters;

  }

  /**
   * @param string $key
   *
   * @return string
   * @throws ReflectionException|Exception
   */
  public static function hashShare ( $key ) {

    return ( new Soda( static::$characters ) )->shortHash( $key );

  }

}
