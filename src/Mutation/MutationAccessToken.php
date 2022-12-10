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

namespace WishKnish\KnishIO\Client\Mutation;

use JsonException;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Exception\UnauthenticatedException;
use WishKnish\KnishIO\Client\Response\ResponseAccessToken;

/**
 * Class MutationAccessToken
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationAccessToken extends Mutation {
    // Query
    protected static string $defaultQuery = 'mutation( $cellSlug: String ) { AccessToken( cellSlug: $cellSlug ) @fields }';

    // Fields
    protected array $fields = [
        'token',
        'time',
    ];

    /**
     * Create a response
     *
     * @param $response
     *
     * @return ResponseAccessToken
     * @throws JsonException
     * @throws InvalidResponseException
     * @throws UnauthenticatedException
     */
    public function createResponse ( $response ): ResponseAccessToken {
        return new ResponseAccessToken( $this, $response );
    }
}
