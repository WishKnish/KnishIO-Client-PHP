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
use WishKnish\KnishIO\Client\Response\ResponseIdentifier;

/**
 * Class QueryLinkIdentifierMutation
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryLinkIdentifierMutation extends Query {
    // Query
    protected static string $defaultQuery = 'mutation( $bundle: String!, $type: String!, $content: String! ) { LinkIdentifier( bundle: $bundle, type: $type, content: $content )
		@fields
	}';

    // Fields
    protected array $fields = [
        'type',
        'bundle',
        'content',
        'set',
        'message',
    ];

    /**
     * Create a response
     *
     * @param string $response
     *
     * @return ResponseIdentifier
     * @throws JsonException
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseIdentifier {
        return new ResponseIdentifier( $this, $response );
    }

}
