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

/**
 * Class ResponseRequestAuthorizationGuest
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseRequestAuthorizationGuest extends Response {

  protected string $dataKey = 'data.AccessToken';

  /**
   * @return string
   */
  public function reason (): string {
    return 'Invalid response from server';
  }

  /**
   * @return bool
   */
  public function success (): bool {
    return $this->payload() !== null;
  }

  /**
   * @return mixed
   */
  public function payload (): mixed {
    return $this->data();
  }

  /**
   * Payload key
   *
   * @param string $key
   *
   * @return mixed
   */
  private function payloadKey ( string $key ): mixed {
    if ( !array_has( $this->payload(), $key ) ) {
      throw new InvalidResponseException( 'ResponseRequestAuthorizationGuest: \'' . $key . '\' key is not found in the payload.' );
    }
    return array_get( $this->payload(), $key );
  }

  /**
   * Token
   *
   * @return string
   */
  public function token (): string {
    return $this->payloadKey( 'token' );
  }

  /**
   * @return mixed
   */
  public function time (): mixed {
    return $this->payloadKey( 'time' );
  }

  /**
   * @return string
   */
  public function pubkey (): string {
    return $this->payloadKey( 'key' );
  }

  /**
   * @return bool
   */
  public function encrypt (): bool {
    return $this->payloadKey( 'encrypt' );
  }
}
