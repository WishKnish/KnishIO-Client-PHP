<?php

namespace WishKnish\KnishIO\Client\Libraries\Jsonld;


/**
 * Class Jsonld
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Jsonld {

	private $context;
	private $graph;
	private $id;
	private $baseUrl;


	/**
	 * @param $data
	 * @return static
	 */
	public static function parse( $json )
	{
		$json = \json_decode( $json, true );

		// Id
		$id = $json[ '@id' ];

		// Base url & init base url
		$parse_url = parse_url( $id );
		$baseUrl = $parse_url[ 'scheme' ] . '://' . $parse_url[ 'host' ];

		// Process graph types
		$graph = [];
		foreach( $json[ '@graph' ] as $item ) {
			$jsonType = JsonldType::parse( $baseUrl, $item );
			$graph[ $jsonType->id() ] = $jsonType;
		}

		// Create new instance
		return new static( $baseUrl, $json[ '@context' ], $graph, $id );
	}


	/**
	 * Jsonld constructor.
	 * @param array $context
	 * @param array $graph
	 * @param string $id
	 */
	public function __construct( string $baseUrl, array $context, array $graph, string $id )
	{
		$this->baseUrl = $baseUrl;
		$this->context = $context;
		$this->graph = $graph;
		$this->id = $id;

		// Link parents with childs
		$this->linkingGraph();
	}


	/**
	 * @return array
	 */
	public function graph()
	{
		return $this->graph;
	}


	/**
	 * @param $type
	 * @return mixed
	 * @throws \Exception
	 */
	public function graphType( $type )
	{
		if ( !array_has( $this->graph, $type ) ) {
			throw new \Exception( 'Graph type ' . $type . ' does not found.' );
		}
		return $this->graph[ $type ];
	}


	/**
	 * Link graph
	 */
	protected function linkingGraph(): void
	{
		// Link parents with childs
		foreach( $this->graph as $item ) {

			// Get parent IDs by domainIncludes property
			$parentIds = $item->parentIds();

			// Link parent item to child
			foreach( $parentIds as $parentId ) {

				// Get parent from graph
				$parent = $this->graph[ $parentId ];

				// Add this field to each parent
				$parent->addField( $item );
			}
		}
	}



	/**
	 * @param $data
	 * @return array
	 */
	public function toJsonldData( $data )
	{
		$jsonldArray = [
			'@context' => $this->baseUrl,
		];

		//
		foreach( $data as $property => $value ) {

			// Add property to the json-ld output if it exists in fields list
			if ( array_has( $this->graph, $property ) ) {
				$jsonldArray[ $property ] = $value;
			}
		}

		return \json_encode( $jsonldArray );
	}


	/**
	 * @return false|string
	 */
	public function toJsonldSchema()
	{
		// Convert graph to json-ld
		$graph = [];
		foreach( $this->graph as $item ) {
			$graph[] = $item->toJsonldSchemaArray();
		}

		// Compile common structure of json data
		$jsonldArray = [
			'@context' => $this->context,
			'@graph' => $graph,
			'@id' => $this->id,
		];

		return \json_encode( $jsonldArray );
	}



}
