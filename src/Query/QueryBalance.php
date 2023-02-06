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
use WishKnish\KnishIO\Client\Response\ResponseBalance;

/**
 * Class QueryBalance
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryBalance extends Query {
    // Query
    protected static string $defaultQuery = 'query( $walletAddress: String, $bundleHash: String, $type: String, $tokenSlug: String, $walletPosition: String ) { Balance( walletAddress: $walletAddress, bundleHash: $bundleHash, type: $type, tokenSlug: $tokenSlug, walletPosition: $walletPosition )
	 	@fields
	 }';

    // Fields
    protected array $fields = [
        'type',
        'walletAddress',
        'bundleHash',
        'tokenSlug',
        'batchId',
        'walletPosition',
        'amount',
        'characters',
        'pubkey',
        'createdAt',
        'tokenUnits' => [
            'id',
            'name',
            'metas',
        ],
        'tradeRates' => [
            'tokenSlug',
            'amount',
        ],
    ];

    /**
     * Create a response
     *
     * @param string $response
     *
     * @return ResponseBalance
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseBalance {
        return new ResponseBalance( $this, $response );
    }

}
