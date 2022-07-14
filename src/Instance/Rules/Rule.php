<?php
namespace WishKnish\KnishIO\Client\Instance\Rules;

use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use JsonSerializable;
use WishKnish\KnishIO\Client\Instance\Rules\Exception\RuleArgumentException;
use WishKnish\KnishIO\Client\Instance\Rules\Exception\RulePropertyException;
use WishKnish\KnishIO\Client\Traits\Json;

/**
 * @property array $condition
 * @property array $callback
 */
class Rule implements JsonSerializable {
  use Json;

  /**
   * @param array $condition
   * @param array $callback
   */
  public function __construct (
    private array $condition = [],
    private array $callback = []
  ) {
  }

  public function __get( $property ) {
    if ( property_exists( $this, $property ) ) {
      throw new RulePropertyException( "Property {static::class}::$$property does not exist." );
    }

    return $this->{ $property };
  }

  /**
   * @throws JsonException
   */
  public function __set( $property, $value ) {

    if (!property_exists( $this, $property ) ) {
      throw new RulePropertyException( "Property $property doesn't exists and cannot be get." );
    }

    if ( !is_array( $value ) ) {
      throw new RuleArgumentException( 'Incorrect rule format! There is no callback field.' );
    }

    if ( mb_strtolower( $property ) === 'callback' ) {

      foreach ( $value as $item ) {
        $this->callback[] = $item instanceof Callback ? $item : Callback::jsonToObject( $item );
      }
    }

    if ( mb_strtolower( $property ) === 'condition' ) {

      foreach ( $value as $item ) {
        $this->callback[] = $item instanceof Condition ? $item : Condition::jsonToObject( $item );
      }
    }

    $this->{ $property } = $value;
  }

  public function  __isset( $property ) {
    return isset( $this->{ $property } );
  }

  public static function arrayToObject ( array $data, ?Rule $object = null ): static {
    $rule = $object ?? new static();

    foreach ( $data[ 'callback' ] as $key => $callback ) {
      $rule->callback[ $key ] = $callback instanceof Callback ? $callback : Callback::arrayToObject( $callback );
    }

    foreach ( $data[ 'condition' ] as $key => $condition ) {
      $rule->condition[ $key ] = $condition instanceof Condition ? $condition : Condition::arrayToObject( $condition );
    }

    return $rule;
  }

  #[ArrayShape( [ 'condition' => "array", 'callback' => "array" ] )] public static function toArray( Rule $object ): array {
    $rule = [ 'condition' => [], 'callback' => [] ];

    foreach ( $object->callback as $key => $value ) {
      $rule[ 'callback' ][ $key ] = $value instanceof Callback ? Callback::toArray( $value ) : $value;
    }

    foreach ( $object->condition as $key => $value ) {
      $rule[ 'condition' ][ $key ] = $value instanceof Condition ? Condition::toArray( $value ) : $value;
    }

    return $rule;
  }

  #[ArrayShape( [ 'condition' => "array", 'callback' => "array" ] )] public function jsonSerialize (): array {
    return  static::toArray( $this );
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
    $rule = json_decode( $string, true, 512, JSON_THROW_ON_ERROR );

    return static::arrayToObject( $rule );
  }
}
