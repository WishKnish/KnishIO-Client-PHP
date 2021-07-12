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

namespace WishKnish\KnishIO\Client\Mutation;

use WishKnish\KnishIO\Client\Response\ResponseRequestAuthorizationGuest;
use WishKnish\KnishIO\Client\Wallet;

class MutationRequestAuthorizationGuest extends Mutation {
  // Query
  protected static $default_query = 'mutation( $cellSlug: String, $pubkey: String, $encrypt: Boolean ) { AccessToken( cellSlug: $cellSlug, pubkey: $pubkey, encrypt: $encrypt ) @fields }';

  protected Wallet $wallet;

  // Fields
  protected $fields = [ 'token', 'time', 'key', 'encrypt' ];

  /**
   * @param Wallet $wallet
   */
  public function setAuthorizationWallet ( Wallet $wallet ): void {
    $this->wallet = $wallet;
  }

  /**
   * @return Wallet|null
   */
  public function getWallet (): ?Wallet {
    return $this->wallet;
  }

  /**
   * Create a response
   *
   * @param $response
   *
   * @return ResponseRequestAuthorizationGuest
   */
  public function createResponse ( $response ): ResponseRequestAuthorizationGuest {
    return new ResponseRequestAuthorizationGuest( $this, $response );
  }
}
