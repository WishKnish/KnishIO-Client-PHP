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

use WishKnish\KnishIO\Client\Exception\CryptoException;
use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;

/**
 * Class Crypto
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Crypto {

  /**
   * @param string|null $seed
   * @param int $length
   *
   * @return string
   */
  public static function generateSecret ( ?string $seed = null, int $length = 2048 ): string {
    // Match JavaScript SDK behavior: outputLen: length * 2 bits = length / 2 bytes
    // JavaScript: for length=2048 → 4096 bits = 512 bytes = 1024 hex chars
    // JavaScript: for length=128 → 256 bits = 32 bytes = 64 hex chars
    return $seed ? bin2hex( Shake256::hash( $seed, $length / 2 ) ) : Strings::randomString( $length );
  }

  /**
   * True when the hash is exactly 64 base-17 symbols (0-9, a-g)
   *
   * @param string|null $hash
   *
   * @return bool
   */
  public static function isSignableHash ( ?string $hash ): bool {
    return $hash !== null && preg_match( '/^[0-9a-g]{64}\z/', $hash ) === 1;
  }

  /**
   * @param string|null $molecularHash
   * @param int|null $index
   *
   * @return string
   * @throws CryptoException
   */
  public static function generateBatchId ( ?string $molecularHash = null, ?int $index = null ): string {

    if ( !in_array( null, [
      $molecularHash,
      $index
    ], true ) ) {
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
   */
  public static function generateBundleHash ( string $secret ): string {
    return bin2hex( Shake256::hash( $secret, 32 ) );
  }

  /**
   * @param string $code
   *
   * @return bool
   */
  public static function isBundleHash ( string $code ): bool {
    return mb_strlen( $code ) === 64 && ctype_xdigit( $code );
  }

  /**
   * SHAKE256 hash function
   *
   * @param string $input - The input string to hash
   * @param int $outputLength - The desired output length in bits
   *
   * @return string - The hex-encoded hash
   */
  public static function shake256 ( string $input, int $outputLength ): string {
    // Convert bits to bytes (outputLength is in bits, Shake256::hash expects bytes)
    return bin2hex( Shake256::hash( $input, $outputLength / 8 ) );
  }

}
