<?php

namespace WishKnish\KnishIO\Client\Libraries\Jsonld;


use WishKnish\KnishIO\Client\Libraries\Jsonld\Validator\Validator;

/**
 * Class JsonldType
 * @package WishKnish\KnishIO\Client\Libraries\Jsonld
 */
class JsonldType
{

	private $contextUrl;

	private $id;
	private $type;
	private $fields = [];

	private $graphProperties;
	private $otherProperties;


	/**
	 * @param $contextUrl
	 * @param $rawId
	 * @return mixed
	 */
	public static function shortId( $contextUrl, $rawId )
	{
		return str_replace( $contextUrl . '/', '', $rawId );
	}


	/**
	 * @param $contextUrl
	 * @param array $rawData
	 * @return static
	 */
	public static function parse( $contextUrl, array $rawData )
	{
		// Prepare id, type & remove it from $rawData
		$id = static::shortId( $contextUrl, array_get( $rawData, '@id') );
		$type = array_get( $rawData, '@type' );
		unset( $rawData['@id'], $rawData['@type'] );

		// Set properties
		$otherProperties = $graphProperties = [];
		foreach( $rawData as $property => $value ) {

			// Graph property
			if ( preg_match( '#^(' . $contextUrl . ').*$#Usi', $property ) ) {

				// Graph property
				$graphValue = [];

				// Array value
				if ( is_array( $value ) ) {

					// Single type => convert to array
					if ( array_has( $value, '@id' ) ) {
						$value = [ $value ];
					}

					// Multiple types
					foreach ( $value as $innerType ) {
						$graphValue[] = static::shortId( $contextUrl, array_get( $innerType, '@id' ) );
					}

				}

				// Add property
				$graphProperties[ static::shortId( $contextUrl, $property ) ] = $graphValue;

			}

			// Other properies (rdfs, rdf, www.w3.org)
			else {
				$otherProperties[ $property ] = $value;
			}

		}

		// Create new jsonldtype object
		return new static( $contextUrl, $id, $type, $graphProperties, $otherProperties );
	}


	/**
	 * JsonldType constructor.
	 * @param array $data
	 */
	public function __construct( $contextUrl, $id, $type, $graphProperties, $otherProperties )
	{
		// Base parameters
		$this->contextUrl = $contextUrl;

		// Prepare id, type & properties
		$this->id = $id;
		$this->type = $type;
		$this->graphProperties = $graphProperties;
		$this->otherProperties = $otherProperties;
	}


	/**
	 * Add field
	 *
	 * @param JsonldType
	 */
	public function addField( JsonldType $field ): void
	{
		$this->fields[ $field->id() ] = $field;
	}


	/**
	 * @return mixed
	 */
	public function id()
	{
		return $this->id;
	}


	/**U
	 * @param $shortId
	 * @return string
	 */
	public function contextId( $shortId )
	{
		return $this->contextUrl . '/' . $shortId;
	}


	/**
	 * @return mixed
	 */
	public function type()
	{
		return $this->type;
	}


	/**
	 * @return array
	 */
	public function fields()
	{
		return $this->fields;
	}


	/**
	 * @return mixed
	 */
	public function parentIds()
	{
		return array_get( $this->graphProperties, 'domainIncludes', [] );
	}


	/**
	 * Get a validator
	 *
	 * @return Validator
	 */
	public function getValidator()
	{
		$types = $this->graphProperties[ 'rangeIncludes' ];

		// @todo: Temporarily code, need custom type, not first
		return Validator::get( $types[ 0 ] );
	}


	/**
	 * @todo: correct fn
	 * @param array $data
	 * @return bool
	 */
	public function validate( array $data ): bool
	{
		foreach( $data as $field => $value ) {

			// Get type by field name
			$type = array_get( $this->fields, $field );

			// The last level: use custom validator
			if ( !$type->fields() && !$type->getValidator()->validate( $value ) ) {
				return false;
			}

			// Composite validator: send fields to next level of types
			if ( $type->fields() && !$type->validate( $value ) ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * @param $data
	 * @return array
	 */
	public function toJsonldDataArray( $data )
	{
		$jsonldArray = [
			'@context' => $this->contextId( $this->id ),
		];

		foreach( $data as $field => $value ) {

			// Add property to the json-ld output if it exists in fields list
			if ( array_has( $this->fields, $property ) ) {
				$jsonldArray[ $property ] = $value;
			}
		}

		return $jsonldArray;
	}


	/**
	 * @return array
	 */
	public function toJsonldSchemaArray(): array
	{
		$jsonldArray = [
			'@id' => $this->contextId( $this->id ),
			'@type' => $this->type,
		];

		// Add graph properties
		foreach( $this->graphProperties as $property => $value ) {

			// Convert property
			$property = $this->contextId( $property );

			// Single property
			if ( count( $value ) === 1 ) {
				$jsonldArray[ $property ] = [ '@id' => $this->contextId( $value[0] ) ];
			}

			// Multiple property
			else {
				$jsonldArray[ $property ] = [];
				foreach( $value as $id ) {
					$jsonldArray[ $property ][] = [ '@id' => $this->contextId( $id ) ];
				}
			}

		}

		// Add other properties
		foreach( $this->otherProperties as $property => $value ) {
			$jsonldArray[ $property ] = $value;
		}

		return $jsonldArray;
	}



}
