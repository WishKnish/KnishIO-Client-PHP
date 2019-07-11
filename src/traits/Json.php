<?php
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
    public function toJson()
    {
        return ( new Serializer( [new ObjectNormalizer(),], [new JsonEncoder(),] ) )->serialize( $this, 'json' );
    }

    /**
     * @param $string
     * @return object
     */
    public static function jsonToObject( $string )
    {
        return ( new Serializer( [new ObjectNormalizer(),], [new JsonEncoder(),] ) )->deserialize( $string, static::class,'json' );
    }
}
