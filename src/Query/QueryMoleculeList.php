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
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Response\ResponseMoleculeList;

/**
 * Class QueryMoleculeList
 * @package WishKnish\KnishIO\Client\Query
 *
 * /graphql?query={Molecule(lastMolecularHashes:[],limit:10,order:"created_at asc"){molecularHash}}
 */
class QueryMoleculeList extends Query {
    // Query
    protected static string $defaultQuery = 'query( $status: String, $molecularHash: String, $lastMolecularHashes: [ String! ], $firstMolecularHashes: [ String! ], $cellSlug: String, $local: Boolean, $algorithm: String, $limit: Int, $order: String ) { Molecule( status: $status, molecularHash: $molecularHash, lastMolecularHashes: $lastMolecularHashes, firstMolecularHashes: $firstMolecularHashes, cellSlug: $cellSlug, local: $local, algorithm: $algorithm, limit: $limit, order: $order )
	 	@fields
	}';

    // Fields
    protected array $fields = [
        'molecularHash',
        'cellSlug',
        'counterparty',
        'bundleHash',
        'createdAt',
        'processedAt',
        'atoms' => [
            'position',
            'isotope',
            'walletAddress',
            'tokenSlug',
            'batchId',
            'value',
            'index',
            'metaType',
            'metaId',
            'metasJson',
            'otsFragment',
            'createdAt',
        ],
    ];

    /**
     * @param string $response
     *
     * @return ResponseMoleculeList
     * @throws JsonException
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseMoleculeList {
        return new ResponseMoleculeList( $this, $response );
    }

}
