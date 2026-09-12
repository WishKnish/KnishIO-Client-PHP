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

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

/**
 * Class SecretStorageException
 * @package WishKnish\KnishIO\Client\Exception
 */
class SecretStorageException extends BaseException {

  /**
   * SecretStorageException constructor.
   *
   * @param string $message
   * @param int $code
   * @param Throwable|null $previous
   */
  public function __construct ( string $message = 'Secret storage operation failed', int $code = 0, ?Throwable $previous = null ) {
    parent::__construct( $message, $code, $previous );
  }

  /**
   * @param string $bundleHash
   * @return static
   */
  public static function notFound ( string $bundleHash ): static {
    return new static( "Secret not found for bundle: {$bundleHash}" );
  }

  /**
   * @param string $reason
   * @return static
   */
  public static function decryptionFailed ( string $reason = 'Decryption failed' ): static {
    return new static( "Failed to decrypt master secret: {$reason}" );
  }

  /**
   * @param string $provider
   * @param string $reason
   * @return static
   */
  public static function unavailable ( string $provider, string $reason = 'Provider unavailable' ): static {
    return new static( "Secret storage provider [{$provider}] unavailable: {$reason}" );
  }

  /**
   * @param string $reason
   * @return static
   */
  public static function validationError ( string $reason ): static {
    return new static( "Secret storage validation error: {$reason}" );
  }
}
