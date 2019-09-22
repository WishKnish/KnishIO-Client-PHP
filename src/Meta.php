<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use WishKnish\KnishIO\Client\Traits\Json;

/**
 * Class Meta
 * @package WishKnish\KnishIO\Client
 *
 * @property string $modelType
 * @property string $modelId
 * @property array $meta
 * @property $snapshotMolecule
 * @property integer $createdAt
 *
 */
class Meta
{
	use Json;

	public $modelType;
	public $modelId;
	public $meta;
	public $snapshotMolecule;
	public $createdAt;

	public function __construct ( $modelType, $modelId, $meta, $snapshotMolecule = null )
	{
		$this->modelType = $modelType;
		$this->modelId = $modelId;
		$this->meta = $meta;
		$this->snapshotMolecule = $snapshotMolecule;
		$this->createdAt = time();
	}

	/**
	 * @param array $meta
	 * @return array
	 */
	public static function normalizeMeta ( array $meta )
	{
		$deep = array_filter( $meta, static function ( $val ) { return is_array( $val ); } );
		$plane = array_filter( $meta, static function ( $val ) { return !is_array( $val ); } );
		return array_replace( $deep,
			array_map( static function ( $key, $val ) {
				return [
					'key'   => $key,
					'value' => $val
				];
			}, array_keys( $plane ), array_values( $plane ) ) );
	}

	/**
	 * @param array $meta
	 * @return array
	 */
	public static function aggregateMeta ( array $meta )
	{
		$aggregate = [];
		if ( count( $meta ) ) {
			foreach ( $meta as $metaEntry ) {
				$aggregate[ $metaEntry[ 'key' ] ] = $metaEntry[ 'value' ];
			}
		}
		return $aggregate;
	}
}
