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
   * @return mixed
   */
  public function token (): mixed {
    return $this->payloadKey( 'token' );
  }

  /**
   * @return mixed
   */
  public function time (): mixed {
    return $this->payloadKey( 'time' );
  }

  /**
   * @return mixed
   */
  public function pubkey (): mixed {
    return $this->payloadKey( 'key' );
  }

  /**
   * @return mixed
   */
  public function encrypt (): mixed {
    return $this->payloadKey( 'encrypt' );
  }
}
