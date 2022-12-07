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
use WishKnish\KnishIO\Client\Response\ResponseBalance;

/**
 * Class QueryBalance
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryBalance extends Query {
    // Query
    protected static string $defaultQuery = 'query( $address: String, $bundleHash: String, $type: String, $token: String, $position: String ) { Balance( address: $address, bundleHash: $bundleHash, type: $type, token: $token, position: $position )
	 	@fields
	 }';

    // Fields
    protected array $fields = [
        'type',
        'address',
        'bundleHash',
        'tokenSlug',
        'batchId',
        'position',
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
     * @throws JsonException
     */
    public function createResponse ( string $response ): ResponseBalance {
        return new ResponseBalance( $this, $response );
    }

}
