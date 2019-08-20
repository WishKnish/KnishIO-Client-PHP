<?php

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
 * @property integer $created_at
 *
 */
class Meta
{
	use Json;

	public $modelType;
	public $modelId;
	public $meta;
	public $snapshotMolecule;
	public $created_at;

	public function __construct ( $modelType, $modelId, $meta, $snapshotMolecule = null )
	{
		$this->modelType = $modelType;
		$this->modelId = $modelId;
		$this->meta = $meta;
		$this->snapshotMolecule = $snapshotMolecule;
		$this->created_at = time();
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
					'key' => $key,
					'value' => $val
				];
			}, array_keys( $plane ), array_values( $plane ) ) );
	}

	/**
	 * @param array $meta
	 * @return array
	 */
	public static function aggregateMeta( array $meta )
	{
		$aggregate = [];
		if ( count($meta) ) {
			foreach ( $meta as $meta_entry ) {
				$aggregate[ $meta_entry['key'] ] = $meta_entry['value'];
			}
		}
		return $aggregate;
	}
}
