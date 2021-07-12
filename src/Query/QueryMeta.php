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

use WishKnish\KnishIO\Client\Response\ResponseMeta;

/**
 * Class QueryMeta
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryMeta extends Query {
  // Query
  protected static $default_query = 'query( $metaType: String, $metaTypes: [ String! ], $metaId: String, $metaIds: [ String! ], $key: String, $keys: [ String! ], $value: String, $values: [ String! ], $count: String ) { MetaType( metaType: $metaType, metaTypes: $metaTypes, metaId: $metaId, metaIds: $metaIds, key: $key, keys: $keys, value: $value, values: $values, count: $count )
		@fields
	}';

  // Fields
  protected $fields = [ 'molecularHash', 'position', 'metaType', 'metaId', 'key', 'value', 'createdAt', ];

  /**
   * @param null $metaType
   * @param null $metaId
   * @param null $key
   * @param null $value
   * @param null $latest
   */
  public static function createVariables ( $metaType = null, $metaId = null, $key = null, $value = null, $latest = null ): array {
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
   * @param $response
   *
   * @return ResponseMeta
   */
  public function createResponse ( $response ) {
    return new ResponseMeta( $this, $response );
  }

}
