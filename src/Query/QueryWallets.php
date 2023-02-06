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

use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Response\ResponseWallets;

/**
 * Class QueryBalance
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryWallets extends Query {
    // Query
    protected static string $defaultQuery = 'query( $walletAddress: String, $bundleHash: String, $tokenSlug: String, $walletPosition: String, $unspent: Boolean ) { Wallets( walletAddress: $walletAddress, bundleHash: $bundleHash, tokenSlug: $tokenSlug, walletPosition: $walletPosition, unspent: $unspent )
	 	@fields
	}';

    // Fields
    protected array $fields = [
        'type',
        'walletAddress',
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
        'walletPosition',
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

    /**
     * @param array|null $variables
     * @param array|null $fields
     *
     * @return ResponseWallets
     * @throws KnishIOException
     * @throws GuzzleException
     * @throws JsonException
     */
    public function execute ( array $variables = null, array $fields = null ): ResponseWallets {
        return parent::execute( $variables, $fields );
    }

}
