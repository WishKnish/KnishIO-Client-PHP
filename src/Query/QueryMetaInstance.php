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
use WishKnish\KnishIO\Client\Response\Response;

/**
 * Class QueryMetaType
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryMetaInstance extends Query {
  // Query
  protected static string $defaultQuery = 'query( $metaType: String!, $metaIds: [ String! ], $keys: [ String! ], $values: [ String! ], $filter: [ MetaFilter! ], $countBy: String, $queryArgs: QueryArgs, $latestMetas: Boolean) { MetaInstance( metaType: $metaType, metaIds: $metaIds, keys: $keys, values: $values, filter: $filter, countBy: $countBy, queryArgs: $queryArgs, latestMetas: $latestMetas )
		@fields
	}';

  // Fields
  protected array $fields = [ 'nodes' => [ 'metaType', 'metaId', 'createdAt', 'metas' => [ 'key', 'value', 'createdAt', ], ], 'counts' => [ 'key', 'value', ], 'paginator' => [ 'offset', 'total' ], ];

  /**
   * @param string $response
   *
   * @return Response
   * @throws JsonException
   */
  public function createResponse ( string $response ): Response {
    return new Response( $this, $response, 'data.MetaInstance' );
  }

}
