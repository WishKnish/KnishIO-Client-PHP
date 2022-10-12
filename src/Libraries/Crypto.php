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

use JsonException;
use ReflectionException;
use SodiumException;
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
  public static function generateSecret ( string $seed = null, int $length = 2048 ): string {
    return $seed ? bin2hex( Shake256::hash( $seed, $length / 4 ) ) : Strings::randomString( $length );
  }

  /**
   * @param string|null $molecularHash
   * @param int|null $index
   *
   * @return string
   * @throws CryptoException
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


}
