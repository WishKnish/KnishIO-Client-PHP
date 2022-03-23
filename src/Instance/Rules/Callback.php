<?php
namespace WishKnish\KnishIO\Client\Instance\Rules;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use WishKnish\KnishIO\Client\Traits\Json;


class Callback {
  use Json;

  public function __construct (
    public string $action,
    public ?Meta $meta = null
  ) {
  }

  /**
   * @return string
   */
  public function toJson (): string {
    return ( new Serializer( [
      new ObjectNormalizer(
        null,
        null,
        null,
        null,
        null,
        null,
        [ AbstractObjectNormalizer::SKIP_NULL_VALUES => true ]
      ),
      ], [ new JsonEncoder(), ] ) )
      ->serialize( $this, 'json', [ AbstractObjectNormalizer::SKIP_NULL_VALUES => true ] );
  }
}
