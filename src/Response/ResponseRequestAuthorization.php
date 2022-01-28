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
 * Class ResponseRequestAuthorization
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseRequestAuthorization extends ResponseMolecule {

  /**
   * Payload key
   *
   * @param $key
   *
   * @return mixed
   */
  private function payloadKey ( $key ): mixed {
    if ( !array_has( $this->payload, $key ) ) {
      throw new InvalidResponseException( 'ResponseRequestAuthorization: \'' . $key . '\' key is not found in the payload.' );
    }
    return array_get( $this->payload, $key );
  }

  /**
   * Access token value
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
