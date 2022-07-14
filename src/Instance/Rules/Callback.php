<?php
namespace WishKnish\KnishIO\Client\Instance\Rules;

use JsonException;
use JsonSerializable;
use WishKnish\KnishIO\Client\Traits\Json;


class Callback implements JsonSerializable {
  use Json;

  public function __construct (
    public string $action,
    public ?string $metaType = null,
    public ?string $metaId = null,
    public ?Meta $meta = null
  ) {
  }

  /**
   * @return string
   * @throws JsonException
   */
  public function toJson (): string {
    return json_encode( static::toArray( $this ), JSON_THROW_ON_ERROR );
  }


  public static function arrayToObject ( array $data, ?Callback $object = null ): static {
    $callback = $object ?? new static( $data[ 'action' ] );

    if ( array_key_exists('metaType', $data ) ) {
      $callback->metaType = $data[ 'metaType' ];
    }

    if ( array_key_exists('metaId', $data ) ) {
      $callback->metaId = $data[ 'metaId' ];
    }

    if ( array_key_exists('meta', $data ) ) {
      $callback->meta = $callback->meta instanceof Meta ? $callback->meta : Meta::arrayToObject( $data[ 'meta' ] );
    }

    return $callback;
  }


  public function jsonSerialize (): array {
   return  static::toArray( $this );
  }

  public static function toArray( Callback $object ): array {
    $normalize = [ 'action' => $object->action ];

    if ( $object->metaType ) {
      $normalize[ 'metaType' ] = $object->metaType;
    }

    if ( $object->metaId ) {
      $normalize[ 'metaId' ] = $object->metaId;
    }

    if ( $object->meta ) {
      $normalize[ 'meta' ] = is_array( $object->meta ) ? $object->meta : Meta::toArray( $object->meta );
    }

    return $normalize;
  }

  /**
   * @throws JsonException
   */
  public static function jsonToObject ( string $string ): static {
    $callback = json_decode( $string, true, 512, JSON_THROW_ON_ERROR );

    return static::arrayToObject( $callback );
  }
}
