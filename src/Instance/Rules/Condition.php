<?php
namespace WishKnish\KnishIO\Client\Instance\Rules;


use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use JsonSerializable;
use WishKnish\KnishIO\Client\Traits\Json;

class Condition implements JsonSerializable {
  use Json;

  public function __construct ( public string $key, public string $value, public string $comparison ) {
  }

  /**
   * @return string
   * @throws JsonException
   */
  public function toJson (): string {
    return json_encode( static::toArray( $this ), JSON_THROW_ON_ERROR );
  }

  /**
   * @throws JsonException
   */
  public static function jsonToObject ( string $string ): static {
    $callback = json_decode( $string, true, 512, JSON_THROW_ON_ERROR );

    return static::arrayToObject( $callback );
  }

  #[ArrayShape( [ 'key' => "string", 'value' => "string", 'comparison' => "string" ] )]
  public static function toArray( Condition $object ): array {
    return [
      'key' => $object->key,
      'value' => $object->value,
      'comparison' => $object->comparison
    ];
  }

  #[ArrayShape( [ 'key' => "string", 'value' => "string", 'comparison' => "string" ] )]
  public function jsonSerialize (): array {
    return  static::toArray( $this );
  }

  public static function arrayToObject ( array $data, ?Condition $object = null ): static {
    if ( $object ) {
      $object->key = $data[ 'key' ];
      $object->value = $data[ 'value' ];
      $object->comparison = $data[ 'comparison' ];
    }

    return $object ?? new static( $data[ 'key' ], $data[ 'value' ], $data[ 'comparison' ] );
  }
}
