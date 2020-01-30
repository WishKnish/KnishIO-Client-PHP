<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Traits;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Trait Json
 * @package WishKnish\KnishIO\Client\Traits
 */
trait Json
{
	/**
	 * @return mixed
	 */
	public function toJson ()
	{
		return ( new Serializer( [ new ObjectNormalizer(), ], [ new JsonEncoder(), ] ) )
			->serialize( $this, 'json' );
	}

	/**
	 * @param $string
	 * @return object
	 */
	public static function jsonToObject ( $string )
	{
		return ( new Serializer( [ new ObjectNormalizer(), ], [ new JsonEncoder(), ] ) )
			->deserialize( $string, static::class, 'json' );
	}
}
