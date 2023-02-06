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
use WishKnish\KnishIO\Client\Response\ResponseMeta;

/**
 * Class QueryMeta
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryMeta extends Query {
    // Query
    protected static string $defaultQuery = 'query( $metaType: String, $metaTypes: [ String! ], $metaId: String, $metaIds: [ String! ], $key: String, $keys: [ String! ], $value: String, $values: [ String! ], $count: String ) { MetaType( metaType: $metaType, metaTypes: $metaTypes, metaId: $metaId, metaIds: $metaIds, key: $key, keys: $keys, value: $value, values: $values, count: $count )
		@fields
	}';

    // Fields
    protected array $fields = [
        'molecularHash',
        'walletPosition',
        'metaType',
        'metaId',
        'key',
        'value',
        'createdAt',
    ];

    /**
     * @param array|string|null $metaType
     * @param array|string|null $metaId
     * @param array|string|null $key
     * @param array|string|null $value
     * @param boolean $latest
     *
     * @return array
     */
    public static function createVariables ( array|string $metaType = null, array|string $metaId = null, array|string $key = null, array|string $value = null, bool $latest = true ): array {
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

        if ( $latest ) {
            $variables[ 'latest' ] = true;
        }

        return $variables;
    }

    /**
     * @param string $response
     *
     * @return ResponseMeta
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseMeta {
        return new ResponseMeta( $this, $response );
    }

}
