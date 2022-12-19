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
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Response\Response;

class MutationActiveSession extends Mutation {

    // Query
    protected static string $defaultQuery = 'mutation( $bundleHash: String!,
      $metaType: String!,
      $metaId: String!,
      $ipAddress: String,
      $browser: String,
      $osCpu: String,
      $resolution: String,
      $timeZone: String,
      $json: String ) {
        ActiveSession(
          bundleHash: $bundleHash,
          metaType: $metaType,
          metaId: $metaId,
          ipAddress: $ipAddress,
          browser: $browser,
          osCpu: $osCpu,
          resolution: $resolution,
          timeZone: $timeZone,
          json: $json
        )
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
        return new Response( $this, $response, 'data.ActiveSession' );
    }
}

