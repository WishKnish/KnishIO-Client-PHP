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
 * Query for retrieving batch history with cascading meta instances
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryBatchHistory extends Query {
  // Query - reuses fields from QueryBatch
  protected static string $defaultQuery = 'query( $batchId: String ) { 
    BatchHistory( batchId: $batchId ) {
      batchId
      type
      createdAt
      wallet {
        address
        bundleHash
        amount
      }
      metas {
        key
        value
      }
    }
  }';

  /**
   * @param string $response
   *
   * @return Response
   * @throws JsonException
   */
  public function createResponse(string $response): Response {
    $responseObject = new Response($this, $response);
    $responseObject->dataKey = 'data.BatchHistory';
    return $responseObject;
  }
}