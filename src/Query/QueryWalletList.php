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
  protected static string $defaultQuery = 'query( $bundleHash: String, $tokenSlug: String, $unspent: Boolean ) { Wallet( bundleHash: $bundleHash, tokenSlug: $tokenSlug, unspent: $unspent )
	 	@fields
	}';

  // Fields
  protected array $fields = [
    'type',
    'address',
    'bundleHash',
    'token' => [
      'name',
      'amount'
    ],
    'molecules' => [
      'molecularHash',
      'createdAt',
    ],
    'tokenUnits' => [
      'id',
      'name',
      'metas',
    ],
    'tradeRates' => [
      'tokenSlug',
      'amount',
    ],
    'tokenSlug',
    'batchId',
    'position',
    'amount',
    'characters',
    'pubkey',
    'createdAt',
  ];

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
