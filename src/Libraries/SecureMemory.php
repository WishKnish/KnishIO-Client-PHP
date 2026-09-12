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

/**
 * Class SecureMemory
 *
 * Memory hygiene and zeroization utilities for sensitive cryptographic material.
 *
 * @package WishKnish\KnishIO\Client\Libraries
 */
class SecureMemory {

  /**
   * Overwrite string contents with zeroes using sodium_memzero and reset reference
   *
   * @param string|null $string
   * @return void
   */
  public static function zeroize ( ?string &$string ): void {
    if ( $string !== null && function_exists( 'sodium_memzero' ) ) {
      sodium_memzero( $string );
    }
    $string = null;
  }

  /**
   * Execute a callback with a sensitive string and guarantee zeroization upon completion
   *
   * @template T
   * @param string $secret
   * @param callable(string): T $callback
   * @return T
   */
  public static function withSecureString ( string $secret, callable $callback ): mixed {
    try {
      return $callback( $secret );
    } finally {
      self::zeroize( $secret );
    }
  }

  /**
   * Constant-time string comparison to prevent timing attacks
   *
   * @param string $a
   * @param string $b
   * @return bool
   */
  public static function constantTimeEquals ( string $a, string $b ): bool {
    return hash_equals( $a, $b );
  }
}
