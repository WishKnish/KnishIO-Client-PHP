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
use WishKnish\KnishIO\Client\Libraries\Base58Static as B58;
use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;

/**
 * Class Soda
 * @package WishKnish\KnishIO\Client\Libraries
 *
 * @property string|null $characters
 *
 */
class Soda {
  /**
   * @var string|null
   */
  public $characters;

  /**
   * Soda constructor.
   *
   * @param string|null $characters
   *
   * @throws ReflectionException
   */
  public function __construct ( string $characters = null ) {

    $constant = Base58::class . '::' . $characters;

    $this->characters = defined( $constant ) ? constant( $constant ) : Base58::GMP;

    if ( !extension_loaded( 'sodium' ) ) {

      Sodium::libsodium2sodium();

    }

  }

  /**
   * Encrypts the given message or data with the recipient's public key
   *
   * @param array|object $message
   * @param string $key
   *
   * @return string
   * @throws SodiumException
   */
  public function encrypt ( $message, string $key ): string {

    return $this->encode( sodium_crypto_box_seal( json_encode( (array) $message ), $this->decode( $key ) ) );

  }

  /**
   * Uses the given private key to decrypt an encrypted message
   *
   * @param string $encrypted
   * @param string $privateKey
   * @param string $publicKey
   *
   * @return array|null
   * @throws SodiumException
   */
  public function decrypt ( string $encrypted, string $privateKey, string $publicKey ): ?array {

    // Get descrypted string
    $decrypted = sodium_crypto_box_seal_open(
        $this->decode( $encrypted ),
        sodium_crypto_box_keypair_from_secretkey_and_publickey(
            $this->decode( $privateKey ),
            $this->decode( $publicKey )
        )
    );

    return json_decode( $decrypted, true );

  }

  /**
   * Derives a private key for encrypting data with the given key
   *
   * @param string $key
   *
   * @return string
   * @throws SodiumException
   * @throws Exception
   */
  public function generatePrivateKey ( string $key ): string {

    return $this->encode( sodium_crypto_box_secretkey( Shake256::hash( $key, SODIUM_CRYPTO_BOX_KEYPAIRBYTES ) ) );

  }

  /**
   * Derives a public key for encrypting data for this wallet's consumption
   *
   * @param string $key
   *
   * @return string
   * @throws SodiumException
   */
  public function generatePublicKey ( string $key ): string {

    return $this->encode( sodium_crypto_box_publickey_from_secretkey( $this->decode( $key ) ) );

  }

  /**
   * @param string $key
   *
   * @return string
   * @throws Exception
   */
  public function shortHash ( string $key ): string {
    return $this->encode( Shake256::hash( $key, 8 ) );
  }

  /**
   * @param string $data
   *
   * @return string
   */
  private function decode ( string $data ): string {

    B58::$options[ 'characters' ] = $this->characters;

    return B58::decode( $data );

  }

  /**
   * @param string $data
   *
   * @return string
   */
  private function encode ( string $data ): string {

    B58::$options[ 'characters' ] = $this->characters;

    return B58::encode( $data );

  }

}
