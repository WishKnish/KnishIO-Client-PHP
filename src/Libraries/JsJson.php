<?php

namespace WishKnish\KnishIO\Client\Libraries;

/**
 * Values shaped so json_encode() writes what JS JSON.stringify() writes for the same JS value:
 * object keys that are array indexes (0 … 2^32-2, no leading zero) first in ascending order, then
 * the other keys in insertion order; objects stay objects (`{}` when empty) and lists stay lists.
 */
final class JsJson {

  /**
   * The JS object with the given entries, keys in JS order (one level).
   *
   * @param array|\stdClass $entries
   *
   * @return \stdClass
   */
  public static function object ( array|\stdClass $entries ): \stdClass {
    if ( $entries instanceof \stdClass ) {
      $entries = get_object_vars( $entries );
    }
    $indexes = [];
    $names = [];
    foreach ( $entries as $key => $value ) {
      if ( self::isArrayIndex( (string) $key ) ) {
        $indexes[ (int) $key ] = $value;
      }
      else {
        $names[] = [ (string) $key, $value ];
      }
    }
    ksort( $indexes );

    $object = new \stdClass();
    foreach ( $indexes as $key => $value ) {
      $object->{(string) $key} = $value;
    }
    foreach ( $names as [ $key, $value ] ) {
      $object->{$key} = $value;
    }
    return $object;
  }

  /**
   * A decoded JSON value with every object, at every depth, in JS key order.
   *
   * @param mixed $value
   *
   * @return mixed
   */
  public static function value ( mixed $value ): mixed {
    if ( $value instanceof \stdClass ) {
      return self::object( array_map( self::value( ... ), get_object_vars( $value ) ) );
    }
    if ( !is_array( $value ) ) {
      return $value;
    }
    if ( array_is_list( $value ) ) {
      return array_map( self::value( ... ), $value );
    }
    return self::object( array_map( self::value( ... ), $value ) );
  }

  /**
   * @param string $key
   *
   * @return bool
   */
  private static function isArrayIndex ( string $key ): bool {
    return strlen( $key ) <= 10 && preg_match( '/^(0|[1-9][0-9]*)$/D', $key ) === 1 && (int) $key < 4294967295;
  }

}
