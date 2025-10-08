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

use WishKnish\KnishIO\Client\Response\ResponseMetaTypeViaAtom;

/**
 * Class QueryMetaTypeViaAtom
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryMetaTypeViaAtom extends Query {

  /**
   * @var string
   */
  protected string $responseClass = ResponseMetaTypeViaAtom::class;

  /**
   * @var string
   */
  protected static string $defaultQuery = 'query ($metaTypes: [String!], $metaIds: [String!], $latest: Boolean, $filter: [MetaFilter!], $queryArgs: QueryArgs, $countBy: String, $atomValues: [String!], $cellSlugs: [String!] ) { MetaTypeViaAtom( metaTypes: $metaTypes, metaIds: $metaIds, atomValues: $atomValues, cellSlugs: $cellSlugs, filter: $filter, latest: $latest, queryArgs: $queryArgs, countBy: $countBy ) @fields }';

  /**
   * QueryMetaTypeViaAtom constructor.
   * @param null $graphQLClient
   */
  public function __construct ( $graphQLClient = null ) {
    $this->fields = [
      'metaType' => null,
      'instanceCount' => [
        'key' => null,
        'value' => null
      ],
      'instances' => [
        'metaType' => null,
        'metaId' => null,
        'createdAt' => null,
        'metas' => [
          'molecularHash' => null,
          'position' => null,
          'key' => null,
          'value' => null,
          'createdAt' => null
        ]
      ],
      'paginatorInfo' => [
        'currentPage' => null,
        'total' => null
      ]
    ];

    parent::__construct( $graphQLClient );
  }

  /**
   * @param array|string|null $metaType
   * @param array|string|null $metaId
   * @param array|string|null $key
   * @param array|string|null $value
   * @param array|null $atomValues
   * @param bool|null $latest
   * @param array|null $filter
   * @param array|null $queryArgs
   * @param string|null $countBy
   * @param string|null $cellSlug
   * @return array
   */
  public static function createVariables ( array|string|null $metaType = null, array|string|null $metaId = null, array|string|null $key = null, array|string|null $value = null, ?array $atomValues = null, ?bool $latest = null, ?array $filter = null, ?array $queryArgs = null, ?string $countBy = null, ?string $cellSlug = null ): array {
    $variables = [];

    if ( $atomValues !== null ) {
      $variables[ 'atomValues' ] = $atomValues;
    }

    if ( $metaType !== null ) {
      $variables[ 'metaTypes' ] = is_string( $metaType ) ? [ $metaType ] : $metaType;
    }

    if ( $metaId !== null ) {
      $variables[ 'metaIds' ] = is_string( $metaId ) ? [ $metaId ] : $metaId;
    }

    if ( $cellSlug !== null ) {
      $variables[ 'cellSlugs' ] = is_string( $cellSlug ) ? [ $cellSlug ] : $cellSlug;
    }

    if ( $countBy !== null ) {
      $variables[ 'countBy' ] = $countBy;
    }

    if ( $filter !== null ) {
      $variables[ 'filter' ] = $filter;
    }

    if ( $key !== null && $value !== null ) {
      if ( !isset( $variables[ 'filter' ] ) ) {
        $variables[ 'filter' ] = [];
      }
      $variables[ 'filter' ][] = [
        'key' => $key,
        'value' => $value,
        'comparison' => '='
      ];
    }

    $variables[ 'latest' ] = $latest === true;

    if ( $queryArgs !== null ) {
      if ( !isset( $queryArgs[ 'limit' ] ) || $queryArgs[ 'limit' ] === 0 ) {
        $queryArgs[ 'limit' ] = '*';
      }
      $variables[ 'queryArgs' ] = $queryArgs;
    }

    return $variables;
  }

  /**
   * @param string $response
   *
   * @return ResponseMetaTypeViaAtom
   * @throws \JsonException
   */
  public function createResponse ( string $response ): ResponseMetaTypeViaAtom {
    return new ResponseMetaTypeViaAtom( $this, $response );
  }
}