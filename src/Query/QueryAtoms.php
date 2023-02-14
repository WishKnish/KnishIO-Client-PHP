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
    protected static string $defaultQuery = 'query( $molecularHash: String, $isotope: Isotope, $metaTypes: [String], $metaIds: [String], $where: QueryAtomsWhereWhereConditions, $latest: Boolean, $orderBy: OrderByClause, $pagination: PaginationClause ) { Atoms( molecularHash: $molecularHash, isotope: $isotope, metaTypes: $metaTypes, metaIds: $metaIds, where: $where, latest: $latest, orderBy: $orderBy, pagination: $pagination )
		@fields
	}';

    // Fields
    protected array $fields = [
        'data' => [
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
            'createdAt'
        ],
        'paginatorInfo' => [
            'count',
            'currentPage',
            'firstItem',
            'hasMorePages',
            'lastItem',
            'lastPage',
            'perPage',
            'total'
        ]
    ];

    /**
     * Builds a GraphQL-friendly variables object based on input fields
     *
     * @param string|null $molecularHash
     * @param string|null $isotope
     * @param string|array|null $metaType
     * @param string|array|null $metaId
     * @param array|null $where
     * @param bool|null $latest
     * @param array|null $orderBy
     * @param array|null $pagination
     *
     * @return array
     */
    public static function createVariables ( string $molecularHash = null, string $isotope = null, string|array $metaType = null, string|array $metaId = null, array $where = null, bool $latest = null, array $orderBy = null, array $pagination = null ): array {
        $variables = [];

        if ( $molecularHash ) {
            $variables[ 'molecularHash' ] = $molecularHash;
        }

        if ( $isotope ) {
            $variables[ 'isotope' ] = $isotope;
        }

        if ( $metaType ) {
            if ( is_string( $metaType ) ) {
                $variables[ 'metaTypes' ] = [ $metaType ];
            }
            else {
                $variables[ 'metaTypes' ] = $metaType;
            }
        }

        if ( $metaId ) {
            if ( is_string( $metaId ) ) {
                $variables[ 'metaIds' ] = [ $metaId ];
            }
            else {
                $variables[ 'metaIds' ] = $metaId;
            }
        }

        if ( $where ) {
            $variables[ 'where' ] = $where;
        }

        if ( $latest ) {
            $variables[ 'latest' ] = $latest;
        }

        // Setting result sort
        if ( $orderBy ) {
            $variables[ 'orderBy' ] = $orderBy;
        }

        // Setting result sort
        if ( $pagination ) {
            $variables[ 'pagination' ] = $pagination;
        }

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
