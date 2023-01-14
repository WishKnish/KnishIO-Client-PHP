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
use WishKnish\KnishIO\Client\Response\Response;

/**
 * Class QueryToken
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryToken extends Query {
    // Query
    protected static string $defaultQuery = 'query( $tokenSlug: String, $tokenSlugs: [ String! ], $limit: Int, $order: String ) { Token( tokenSlug: $tokenSlug, tokenSlugs: $tokenSlugs, limit: $limit, order: $order )
	 	@fields
	 }';

    // Fields
    protected array $fields = [
        'tokenSlug',
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
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): Response {
        return new Response( $this, $response, 'data.Token' );
    }

}
