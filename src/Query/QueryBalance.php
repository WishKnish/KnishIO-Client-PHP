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
    protected static string $defaultQuery = 'query( $address: String, $bundleHash: String, $type: String, $tokenSlug: String, $position: String ) { Balance( address: $address, bundleHash: $bundleHash, type: $type, tokenSlug: $tokenSlug, position: $position )
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
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseBalance {
        return new ResponseBalance( $this, $response );
    }

}
