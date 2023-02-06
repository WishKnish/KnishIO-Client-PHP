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
use WishKnish\KnishIO\Client\Response\ResponseBatch;

/**
 * Class QueryMetaBatch
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryBatch extends Query {
    // Query
    protected static string $defaultQuery = 'query( $batchId: String ) { Batch( batchId: $batchId )
		@fields
	}';

    // Fields
    protected array $fields = [
        'batchId',
        'type',
        'createdAt',
        'wallet' => [
            'walletAddress',
            'bundleHash',
            'amount',
        ],
        'metas' => [
            'key',
            'value',
        ],
    ];

    /**
     * @param string $response
     *
     * @return ResponseBatch
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseBatch {
        return new ResponseBatch( $this, $response );
    }

}
