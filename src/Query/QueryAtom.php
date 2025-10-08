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
use WishKnish\KnishIO\Client\Response\ResponseAtom;

/**
 * Query for getting atom instances with comprehensive filtering
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryAtom extends Query {
  // Query
  protected static string $defaultQuery = 'query(
    $molecularHashes: [String!],
    $bundleHashes: [String!],
    $positions:[String!],
    $walletAddresses: [String!],
    $isotopes: [String!],
    $tokenSlugs: [String!],
    $cellSlugs: [String!],
    $batchIds: [String!],
    $values: [String!],
    $metaTypes: [String!],
    $metaIds: [String!],
    $indexes: [String!],
    $filter: [ MetaFilter! ],
    $latest: Boolean,
    $queryArgs: QueryArgs,
  ) {
    Atom(
      molecularHashes: $molecularHashes,
      bundleHashes: $bundleHashes,
      positions: $positions,
      walletAddresses: $walletAddresses,
      isotopes: $isotopes,
      tokenSlugs: $tokenSlugs,
      cellSlugs: $cellSlugs,
      batchIds: $batchIds,
      values: $values,
      metaTypes: $metaTypes,
      metaIds: $metaIds,
      indexes: $indexes,
      filter: $filter,
      latest: $latest,
      queryArgs: $queryArgs,
    ) @fields
  }';

  // Fields
  protected array $fields = [
    'instances' => [
      'position',
      'walletAddress',
      'tokenSlug',
      'isotope',
      'index',
      'molecularHash',
      'metaId',
      'metaType',
      'metasJson',
      'batchId',
      'value',
      'bundleHashes',
      'cellSlugs',
      'createdAt',
      'otsFragment'
    ],
    'paginatorInfo' => [
      'currentPage',
      'total'
    ]
  ];

  /**
   * Create variables for the query from individual and array parameters
   *
   * @param array $params
   * @return array
   */
  public static function createVariables(array $params): array {
    $variables = [];
    
    // Map of singular to plural parameter names
    $paramMap = [
      'molecularHash' => 'molecularHashes',
      'bundleHash' => 'bundleHashes',
      'position' => 'positions',
      'walletAddress' => 'walletAddresses',
      'isotope' => 'isotopes',
      'tokenSlug' => 'tokenSlugs',
      'cellSlug' => 'cellSlugs',
      'batchId' => 'batchIds',
      'value' => 'values',
      'metaType' => 'metaTypes',
      'metaId' => 'metaIds',
      'index' => 'indexes'
    ];
    
    // Process singular parameters into arrays
    foreach ($paramMap as $singular => $plural) {
      if (isset($params[$singular])) {
        $variables[$plural] = $variables[$plural] ?? [];
        $variables[$plural][] = $params[$singular];
      }
    }
    
    // Pass through array parameters
    foreach ($paramMap as $singular => $plural) {
      if (isset($params[$plural])) {
        $variables[$plural] = $params[$plural];
      }
    }
    
    // Pass through other parameters
    if (isset($params['filter'])) {
      $variables['filter'] = $params['filter'];
    }
    if (isset($params['latest'])) {
      $variables['latest'] = $params['latest'];
    }
    if (isset($params['queryArgs'])) {
      $variables['queryArgs'] = $params['queryArgs'];
    }
    
    return $variables;
  }

  /**
   * @param string $response
   *
   * @return ResponseAtom
   * @throws JsonException
   */
  public function createResponse(string $response): ResponseAtom {
    return new ResponseAtom($this, $response);
  }
}