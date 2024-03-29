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

namespace WishKnish\KnishIO\Client\Query;

use JsonException;
use WishKnish\KnishIO\Client\Response\ResponseWalletList;

/**
 * Class QueryBalance
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryWalletList extends Query {
  // Query
  protected static string $defaultQuery = 'query( $address: String, $bundleHash: String, $token: String, $position: String ) { Wallet( address: $address, bundleHash: $bundleHash, token: $token, position: $position )
	 	@fields
	}';

  // Fields
  protected array $fields = [ 'address', 'bundleHash', 'token' => [ 'name', 'amount' ], 'molecules' => [ 'molecularHash', 'createdAt', ], 'tokenSlug', 'batchId', 'position', 'amount', 'characters', 'pubkey', 'createdAt', ];

  /**
   * @param string $response
   *
   * @return ResponseWalletList
   * @throws JsonException
   */
  public function createResponse ( string $response ): ResponseWalletList {
    return new ResponseWalletList( $this, $response );
  }

}
