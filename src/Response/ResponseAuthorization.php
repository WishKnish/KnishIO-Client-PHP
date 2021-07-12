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

namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class ResponseAuthorization
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseAuthorization extends ResponseMolecule {

  /**
   * Payload key
   *
   * @param $key
   *
   * @return mixed
   */
  private function payloadKey ( $key ) {
    if ( !array_has( $this->payload, $key ) ) {
      throw new InvalidResponseException( 'ResponseAuthorization: \'' . $key . '\' key is not found in the payload.' );
    }
    return array_get( $this->payload, $key );
  }

  /**
   * Token
   */
  public function token () {
    return $this->payloadKey( 'token' );
  }

  /**
   * @return mixed
   */
  public function time () {
    return $this->payloadKey( 'time' );
  }

  /**
   * @return mixed
   */
  public function pubKey () {
    return $this->payloadKey( 'key' );
  }

  public function wallet (): Wallet {
    return $this->clientMolecule()
        ->sourceWallet();
  }

  public function encrypt () {
    return $this->payloadKey( 'encrypt' );
  }

}
