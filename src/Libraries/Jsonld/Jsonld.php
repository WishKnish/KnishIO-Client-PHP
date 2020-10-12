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
			$graph[] = JsonldType::parse( $baseUrl, $item );
		}

		// Create new instance
		return new static( $json[ '@context' ], $graph, $id );
	}


	/**
	 * Jsonld constructor.
	 * @param array $context
	 * @param array $graph
	 * @param string $id
	 */
	public function __construct( array $context, array $graph, string $id )
	{
		$this->context = $context;
		$this->graph = $graph;
		$this->id = $id;
	}


	/**
	 * @return false|string
	 */
	public function toJsonld()
	{
		// Convert graph to json-ld
		$graph = [];
		foreach( $this->graph as $item ) {
			$graph[] = $item->toJsonldArray();
		}

		return \json_encode( [
			'@context' => $this->context,
			'@graph' => $graph,
			'@id' => $this->id,
		] );
	}



}
