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

use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseMetaType;

/**
 * Class QueryMetaType
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryMetaInstance extends Query {
  // Query
  protected static $default_query = 'query( $metaType: String!, $metaIds: [ String! ], $keys: [ String! ], $values: [ String! ], $filter: [ MetaFilter! ], $countBy: String, $queryArgs: QueryArgs, $latestMetas: Boolean) { MetaInstance( metaType: $metaType, metaIds: $metaIds, keys: $keys, values: $values, filter: $filter, countBy: $countBy, queryArgs: $queryArgs, latestMetas: $latestMetas )
		@fields
	}';

  // Fields
  protected $fields = [ 'nodes' => [ 'metaType', 'metaId', 'createdAt', 'metas' => [ 'key', 'value', 'createdAt', ], ], 'counts' => [ 'key', 'value', ], 'paginator' => [ 'offset', 'total' ], ];

  /**
   * @param $response
   *
   * @return Response
   */
  public function createResponse ( $response ) {
    return new Response( $this, $response, 'data.MetaInstance' );
  }

}
