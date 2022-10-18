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
    public ?Meta $meta = null,
    public ?string $address = null,
    public ?string $token = null,
    public ?string $amount = null,
    public ?string $comparison = null
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

    if ( array_key_exists('address', $data ) ) {
      $callback->address = $data[ 'address' ];
    }

    if ( array_key_exists('token', $data ) ) {
      $callback->token = $data[ 'token' ];
    }

    if ( array_key_exists('amount', $data ) ) {
      $callback->amount = $data[ 'amount' ];
    }

    if ( array_key_exists('comparison', $data ) ) {
      $callback->comparison = $data[ 'comparison' ];
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

    if ( $object->address ) {
      $normalize[ 'address' ] = $object->address;
    }

    if ( $object->token ) {
      $normalize[ 'token' ] = $object->token;
    }

    if ( $object->metaId ) {
      $normalize[ 'amount' ] = $object->amount;
    }

    if ( $object->metaId ) {
      $normalize[ 'comparison' ] = $object->comparison;
    }

    return $normalize;
  }

  private function is ( string $type ): bool {
    return strtolower( $this->action ) === strtolower( $type );
  }

  public function isReject (): bool {
    return $this->is( 'reject' );
  }

  public function isMeta (): bool {
    $prop = array_intersect(['action', 'metaId', 'metaType', 'meta'], array_keys($this->jsonSerialize()));

    return count( $prop ) === 4 && $this->is( 'meta' );
  }

  public function isCollect (): bool {
    $prop = array_intersect(['action', 'address', 'token', 'amount', 'comparison'], array_keys($this->jsonSerialize()));

    return count( $prop ) === 5 && $this->is( 'collect' );
  }

  public function isBuffer (): bool {
    $prop = array_intersect(['action', 'address', 'token', 'amount', 'comparison'], array_keys($this->jsonSerialize()));

    return count( $prop ) === 5 && $this->is( 'buffer' );
  }

  public function isRemit (): bool {
    $prop = array_intersect(['action', 'token', 'amount'], array_keys($this->jsonSerialize()));

    return count( $prop ) === 3 && $this->is( 'buffer' );
  }

  public function isBurn (): bool {
    $prop = array_intersect(['action', 'token', 'amount', 'comparison'], array_keys($this->jsonSerialize()));

    return count( $prop ) === 4 && $this->is( 'buffer' );
  }

  /**
   * @throws JsonException
   */
  public static function jsonToObject ( string $string ): static {
    $callback = json_decode( $string, true, 512, JSON_THROW_ON_ERROR );

    return static::arrayToObject( $callback );
  }
}
