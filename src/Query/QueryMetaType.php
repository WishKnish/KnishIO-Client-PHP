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

use WishKnish\KnishIO\Client\Response\ResponseMetaType;

/**
 * Class QueryMetaType
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryMetaType extends Query {
  // Query
  protected static string $default_query = 'query( $metaType: String, $metaTypes: [ String! ], $metaId: String, $metaIds: [ String! ], $key: String, $keys: [ String! ], $value: String, $values: [ String! ], $filter: [ MetaFilter! ], $count: String, $countBy: String, $queryArgs: QueryArgs, $latestMetas: Boolean, $latest: Boolean) { MetaType( metaType: $metaType, metaTypes: $metaTypes, metaId: $metaId, metaIds: $metaIds, key: $key, keys: $keys, value: $value, values: $values, filter: $filter, count: $count, countBy: $countBy, queryArgs: $queryArgs, latestMetas: $latestMetas, latest: $latest )
		@fields
	}';

  // Fields
  protected array $fields = [ 'metaType', 'instances' => [ 'metaType', 'metaId', 'createdAt', 'metas' => [ 'molecularHash', 'position', 'metaType', 'metaId', 'key', 'value', 'createdAt', ], 'atoms' => [ 'molecularHash', 'position', 'isotope', 'walletAddress', 'tokenSlug', 'batchId', 'value', 'index', 'metaType', 'metaId', 'otsFragment', 'createdAt', ], 'molecules' => [ 'molecularHash', 'cellSlug', 'bundleHash', 'status', 'height', 'createdAt', 'receivedAt', 'processedAt', 'broadcastedAt', ], ], 'metas' => [ 'molecularHash', 'position', 'metaType', 'metaId', 'key', 'value', 'createdAt', ], 'paginatorInfo' => [ 'currentPage', 'total' ], 'createdAt', ];

  /**
   * Builds a GraphQL-friendly variables object based on input fields
   *
   * @param string|array|null $metaType
   * @param string|array|null $metaId
   * @param string|array|null $key
   * @param string|array|null $value
   * @param bool $latest
   *
   * @return array
   */
  public static function createVariables ( $metaType = null, $metaId = null, $key = null, $value = null, bool $latest = null ): array {
    $variables = [];

    if ( $metaType ) {
      $variables[ is_string( $metaType ) ? 'metaType' : 'metaTypes' ] = $metaType;
    }

    if ( $metaId ) {
      $variables[ is_string( $metaId ) ? 'metaId' : 'metaIds' ] = $metaId;
    }

    if ( $key ) {
      $variables[ is_string( $key ) ? 'key' : 'keys' ] = $key;
    }

    if ( $value ) {
      $variables[ is_string( $value ) ? 'value' : 'values' ] = $value;
    }

    if ( $latest ) {
      $variables[ 'latest' ] = true;
    }

    return $variables;
  }

  /**
   * @param string $response
   *
   * @return ResponseMetaType
   */
  public function createResponse ( string $response ): ResponseMetaType {
    return new ResponseMetaType( $this, $response );
  }

}
