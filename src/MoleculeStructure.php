<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;


use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Traits\Json;


/**
 * Class MoleculeStructure
 * @package WishKnish\KnishIO\Client
 */
class MoleculeStructure {

	use Json;

	static protected $cellSlugDelimiter = '.';

	public $molecularHash;
	public $cellSlug;
	public $bundle;
	public $status;
	public $createdAt;
	public $atoms;

	protected $cellSlugOrigin;


	/**
	 * MoleculeStructure constructor.
	 * @param null $cellSlug
	 */
	public function __construct( $cellSlug = null )
	{
		$this->cellSlugOrigin = $this->cellSlug = $cellSlug;
	}


	/**
	 * @return string
	 */
	public function cellSlugBase ()
	{
		return explode( static::$cellSlugDelimiter, $this->cellSlug )[0];
	}


	/**
	 * @param Wallet|null $senderWallet
	 * @return bool
	 * @throws ReflectionException
	 */
	public function check ( Wallet $senderWallet = null )
	{
		return CheckMolecule::verify( $this, $senderWallet );
	}


	/**
	 * @return string
	 */
	public function __toString ()
	{
		return ( string ) $this->toJson();
	}


	/**
	 * @param string $string
	 * @return object
	 */
	public static function jsonToObject ( $string )
	{
		$serializer = new Serializer( [ new ObjectNormalizer(), ], [ new JsonEncoder(), ] );
		$object = $serializer->deserialize( $string, static::class, 'json' );

		foreach ( $object->atoms as $idx => $atom ) {
			$object->atoms[ $idx ] = Atom::jsonToObject( $serializer->serialize( $atom, 'json' ) );
		}

		$object->atoms = Atom::sortAtoms( $object->atoms );

		return $object;
	}

}
