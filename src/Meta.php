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
		$result = [];
		foreach ( $meta as $key => $value ) {
			$result[] = is_array( $value ) ? $value : [ 'key' => $key, 'value' => $value, ];
		}
		return $result;
	}

	/**
	 * @param array|object $meta
	 * @return array
	 */
	public static function aggregateMeta ( $meta )
	{
		$aggregate = [];
		if ( count( $meta ) ) {
			foreach ( $meta as $metaEntry ) {
				if ( is_object( $metaEntry ) ) {
					$metaKey = $metaEntry->key;
					$metaValue = $metaEntry->value;
				} else {
					$metaKey = $metaEntry[ 'key' ];
					$metaValue = $metaEntry[ 'value' ];
				}

				$aggregate[ $metaKey ] = $metaValue;
			}
		}
		return $aggregate;
	}
}
