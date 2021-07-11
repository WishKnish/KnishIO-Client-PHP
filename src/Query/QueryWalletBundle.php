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

use WishKnish\KnishIO\Client\Response\ResponseWalletBundle;

/**
 * Class QueryWalletBundle
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryWalletBundle extends Query
{

	// Query
	protected static $default_query = 'query( $bundleHash: String, $bundleHashes: [ String! ], $key: String, $keys: [ String! ], $value: String, $values: [ String! ], $keys_values: [ MetaInput ], $latest: Boolean, $limit: Int, $order: String ) { WalletBundle( bundleHash: $bundleHash, bundleHashes: $bundleHashes, key: $key, keys: $keys, value: $value, values: $values, keys_values: $keys_values, latest: $latest, limit: $limit, order: $order )
	 	@fields
	}';



	// Fields
	protected $fields = [
		'bundleHash',
		'slug',
		'metas' => [
			'molecularHash',
			'position',
			'metaType',
			'metaId',
			'key',
			'value',
			'createdAt',
		],
	//	'molecules',
	//	'wallets',
		'createdAt',
	];



  /**
   * Builds a GraphQL-friendly variables object based on input fields
   *
   * @param string|null|array $bundleHash
   * @param string|null|array $key
   * @param string|null|array $value
   * @param bool $latest
   *
   * @return array
   */
  public static function createVariables ( $bundleHash = null, $key = null, $value = null, bool $latest = true ) {

    $variables = [
      'latest' => $latest,
    ];

    if ( $bundleHash ) {
      $variables[ is_string( $bundleHash ) ? 'bundleHash' : 'bundleHashes' ] = $bundleHash;
    }

    if ( $key ) {
      $variables[ is_string( $key ) ? 'key' : 'keys' ] = $key;
    }

    if ( $value ) {
      $variables[ is_string( $value ) ? 'value' : 'values' ] = $value;
    }

    return $variables;

}


	/**
	 * @param string $response
	 * @return \WishKnish\KnishIO\Client\Response\Response|ResponseWalletBundle
	 */
	public function createResponse ($response) {
		return new ResponseWalletBundle($this, $response);
	}



}
