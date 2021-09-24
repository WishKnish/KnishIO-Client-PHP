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

use WishKnish\KnishIO\Client\Response\ResponseContinuId;

/**
 * Class QueryContinuId
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryContinuId extends Query {
  // Query
  /**
   * @var string
   */
  protected static string $default_query = 'query ($bundle: String!) { ContinuId(bundle: $bundle)
    	@fields
    }';

  // Fields
  protected array $fields = [ 'address', 'bundleHash', 'tokenSlug', 'position', 'batchId', 'characters', 'pubkey', 'amount', 'createdAt', ];

  /**
   * Create a response
   *
   * @param string $response
   *
   * @return ResponseContinuId
   */
  public function createResponse ( string $response ): ResponseContinuId {
    return new ResponseContinuId( $this, $response );
  }
}
