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
use WishKnish\KnishIO\Client\Response\ResponseBalance;

/**
 * Class QueryToken
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryToken extends Query {
  // Query
  protected static string $default_query = 'query( $slug: String, $slugs: [ String! ], $limit: Int, $order: String ) { Token( slug: $slug, slugs: $slugs, limit: $limit, order: $order )
	 	@fields
	 }';

  // Fields
  protected array $fields = [
    'slug',
    'name',
    'fungibility',
    'supply',
    'decimals',
    'amount',
    'icon',
  ];

  /**
   * @param string $response
   *
   * @return Response
   */
  public function createResponse ( string $response ): Response {
    return new Response( $this, $response, 'data.Token' );
  }

}
