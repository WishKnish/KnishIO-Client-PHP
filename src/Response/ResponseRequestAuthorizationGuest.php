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

class ResponseRequestAuthorizationGuest extends Response {

  protected string $dataKey = 'data.AccessToken';

  public function reason (): string {
    return 'Invalid response from server';
  }

  public function success (): bool {
    return $this->payload() !== null;
  }

  public function payload () {
    return $this->data();
  }

  /**
   * Payload key
   *
   * @param $key
   *
   * @return mixed
   */
  private function payloadKey ( $key ) {
    if ( !array_has( $this->payload(), $key ) ) {
      throw new InvalidResponseException( 'ResponseRequestAuthorizationGuest: \'' . $key . '\' key is not found in the payload.' );
    }
    return array_get( $this->payload(), $key );
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

  /**
   * @return Wallet
   */
  public function wallet (): Wallet {
    return $this->query()
        ->getWallet();
  }

  /**
   * @return mixed
   */
  public function encrypt () {
    return $this->payloadKey( 'encrypt' );
  }
}
