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
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseQueryActiveSession;

/**
 * Class QueryActiveSession
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryActiveSession extends Query {

    // Query
    protected static string $defaultQuery = 'query( $bundleHash: String, $metaType: String, $metaId: String ) { ActiveUser( bundleHash: $bundleHash, metaType: $metaType, metaId: $metaId )
	 	@fields
	 }';

    // Fields
    protected array $fields = [
        'bundleHash',
        'metaType',
        'metaId',
        'jsonData',
        'createdAt',
        'updatedAt',
    ];

    /**
     * @param string $response
     *
     * @return Response
     * @throws JsonException
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): Response {
        return new ResponseQueryActiveSession( $this, $response );
    }

}
