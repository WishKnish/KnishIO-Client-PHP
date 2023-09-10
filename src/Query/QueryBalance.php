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
    protected static string $defaultQuery = 'query( $bundleHash: String, $tokenSlug: String, $batchId: String ) { Balance( bundleHash: $bundleHash, tokenSlug: $tokenSlug, batchId: $batchId )
	 	@fields
	 }';

    // Fields
    protected array $fields = [
        'walletAddress',
        'walletPosition',
        'tokenSlug',
        'token' => [
            'name',
            'fungibility',
            'supply',
            'decimals',
            'amount',
            'icon',
            'createdAt'
        ],
        'value',
        'valueUnits' => [
            'id',
            'name',
            'metas',
        ],
        'swapRates' => [
            'tokenSlug',
            'amount',
        ],
        'batchId',
        'characters',
        'pubkey',
        'type',
        'createdAt',
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
