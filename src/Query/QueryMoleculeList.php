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
use WishKnish\KnishIO\Client\Response\ResponseMoleculeList;

/**
 * Class QueryMoleculeList
 * @package WishKnish\KnishIO\Client\Query
 *
 * /graphql?query={Molecule(lastMolecularHash:"",limit:10,order:"created_at asc"){molecularHash}}
 */
class QueryMoleculeList extends Query {
  // Query
  protected static string $default_query = 'query( $status: String, $lastMolecularHash: String, $local: Boolean, $limit: Int, $order: String ) { Molecule( status: $status, lastMolecularHash: $lastMolecularHash, local: $local, limit: $limit, order: $order )
	 	@fields
	}';

  // Fields
  protected array $fields = [ 'molecularHash', 'cellSlug', 'counterparty', 'bundleHash', 'createdAt', 'atoms' => [ 'position', 'isotope', 'walletAddress', 'tokenSlug', 'batchId', 'value', 'index', 'metaType', 'metaId', 'metasJson', 'otsFragment', 'createdAt', ], ];

  /**
   * @param string $response
   *
   * @return ResponseMoleculeList
   * @throws JsonException
   */
  public function createResponse ( string $response ): ResponseMoleculeList {
    return new ResponseMoleculeList( $this, $response );
  }

}
