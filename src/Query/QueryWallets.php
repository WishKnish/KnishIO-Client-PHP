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

use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Response\ResponseWallets;

/**
 * Class QueryBalance
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryWallets extends Query {
    // Query
    protected static string $defaultQuery = 'query( $address: String, $bundleHash: String, $tokenSlug: String, $position: String, $unspent: Boolean ) { Wallets( address: $address, bundleHash: $bundleHash, tokenSlug: $tokenSlug, position: $position, unspent: $unspent )
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
     * @return ResponseWallets
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseWallets {
        return new ResponseWallets( $this, $response );
    }

}
