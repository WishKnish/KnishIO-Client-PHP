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


/**
 * Class ResponseRequestAuthorizationGuest
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseRequestAuthorizationGuest extends Response {

  /**
   * @var string
   */
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
   * @return string|null
   */
  public function token (): ?string {
    return array_get( $this->payload(), 'token' );
  }

  /**
   * @return int|null
   */
  public function time (): ?int {
    return array_get( $this->payload(), 'time' );
  }

  /**
   * @return string|null
   */
  public function pubkey (): ?string {
    return array_get( $this->payload(), 'key' );
  }

  /**
   * @return bool|null
   */
  public function encrypt (): ?bool {
    return array_get( $this->payload(), 'encrypt' );
  }

}
