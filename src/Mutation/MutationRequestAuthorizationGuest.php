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
  protected static string $default_query = 'mutation( $cellSlug: String, $pubkey: String, $encrypt: Boolean ) { AccessToken( cellSlug: $cellSlug, pubkey: $pubkey, encrypt: $encrypt ) @fields }';

  // Fields
  protected array $fields = [
      'token',
      'expiresAt',
      'pubkey',

      // Deprecated fields
      'time',
      'key',
      'encrypt',
  ];


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
