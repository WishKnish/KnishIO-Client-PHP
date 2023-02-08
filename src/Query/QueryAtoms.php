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
use WishKnish\KnishIO\Client\Response\ResponseAtoms;

/**
 * Class QueryAtoms
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryAtoms extends Query {
    // Query
    protected static string $defaultQuery = 'query( $filter: [AtomFilter] ) { Atoms( filter: $filter )
		@fields
	}';

    // Fields
    protected array $fields = [
        'molecularHash',
        'walletPosition',
        'isotope',
        'walletAddress',
        'tokenSlug',
        'batchId',
        'value',
        'index',
        'metaType',
        'metaId',
        'metas' => [
            'key',
            'value'
        ],
        'otsFragment',
        'createdAt',
    ];

    /**
     * Builds a GraphQL-friendly variables object based on input fields
     *
     * @param array|string|null $metaType
     * @param array|string|null $metaId
     * @param array|string|null $key
     * @param array|string|null $value
     * @param bool $latest
     *
     * @return array
     */
    public static function createVariables ( array|string $metaType = null, array|string $metaId = null, array|string $key = null, array|string $value = null, bool $latest = false ): array {
        $variables = [];

        if ( $metaType ) {
            $variables[ is_string( $metaType ) ? 'metaType' : 'metaTypes' ] = $metaType;
        }

        if ( $metaId ) {
            $variables[ is_string( $metaId ) ? 'metaId' : 'metaIds' ] = $metaId;
        }

        if ( $key ) {
            $variables[ is_string( $key ) ? 'key' : 'keys' ] = $key;
        }

        if ( $value ) {
            $variables[ is_string( $value ) ? 'value' : 'values' ] = $value;
        }

        $variables[ 'latest' ] = $latest;

        return $variables;
    }

    /**
     * @param string $response
     *
     * @return ResponseAtoms
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseAtoms {
        return new ResponseAtoms( $this, $response );
    }

}
