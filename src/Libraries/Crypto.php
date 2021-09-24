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

use Exception;
use ReflectionException;
use SodiumException;
use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;

/**
 * Class Crypto
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Crypto {

  /**
   * @var string
   */
  private static string $characters = Base58::GMP;

  /**
   * Generates a secret based on an optional seed
   *
   * @param string|null $seed
   * @param int|null $length
   *
   * @return string
   * @throws Exception
   */
  public static function generateSecret ( string $seed = null, int $length = null ): string {
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
  public static function generateBatchId ( ?string $molecularHash = null, ?int $index = null ): string {

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
  public static function generateBundleHash ( string $secret ): string {

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
  public static function encryptMessage ( $message, string $key ): ?string {

    return ( new Soda( static::$characters ) )->encrypt( $message, $key );

  }

  /**
   * Uses the given private key to decrypt an encrypted message
   *
   * @param string $encrypted
   * @param string $privateKey
   * @param string $publicKey
   *
   * @return array|null
   * @throws ReflectionException|SodiumException
   */
  public static function decryptMessage ( string $encrypted, string $privateKey, string $publicKey ): ?array {

    return ( new Soda( static::$characters ) )->decrypt( $encrypted, $privateKey, $publicKey );

  }

  /**
   * Derives a private key for encrypting data with the given key
   *
   * @param string|null $key
   *
   * @return string|null
   * @throws Exception|ReflectionException
   */
  public static function generateEncPrivateKey ( string $key = null ): ?string {

    return ( new Soda( static::$characters ) )->generatePrivateKey( $key );

  }

  /**
   * Derives a public key for encrypting data for this wallet's consumption
   *
   * @param string $key
   *
   * @return string|null
   * @throws ReflectionException|SodiumException
   */
  public static function generateEncPublicKey ( string $key ): ?string {

    return ( new Soda( static::$characters ) )->generatePublicKey( $key );

  }

  /**
   * @param string $characters
   */
  public static function setCharacters ( string $characters ): void {

    $constant = Base58::class . '::' . $characters;

    static::$characters = defined( $constant ) ? constant( $constant ) : static::$characters;

  }

  /**
   * @return string
   */
  public static function getCharacters (): string {

    return static::$characters;

  }

  /**
   * @param string $key
   *
   * @return string
   * @throws ReflectionException|Exception
   */
  public static function hashShare ( string $key ): string {

    return ( new Soda( static::$characters ) )->shortHash( $key );

  }

}
