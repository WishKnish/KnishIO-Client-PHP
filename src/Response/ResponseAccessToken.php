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

class ResponseAccessToken extends Response
{
  protected $dataKey = 'data.AccessToken';

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
   * @return mixed
   */
  private function payloadKey ( $key ) {
    if (!array_has( $this->payload(), $key ) ) {
      throw new InvalidResponseException( 'ResponseAccessToken: \''.$key.'\' key is not found in the payload.' );
    }
    return array_get( $this->payload(), $key );
  }


  /**
   * Token
   */
  public function token () {
    return $this->payloadKey('token' );
  }


  /**
   * @return mixed
   */
  public function time () {
    return $this->payloadKey( 'time' );
  }

}
