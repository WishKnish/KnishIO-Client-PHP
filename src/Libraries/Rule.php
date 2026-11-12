<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Libraries;

use stdClass;
use WishKnish\KnishIO\Client\Exception\CodeException;
use WishKnish\KnishIO\Client\Exception\MetaMissingException;
use WishKnish\KnishIO\Client\Exception\RuleArgumentException;

/**
 * The rule list of an R-isotope atom, validated and serialized as JS instance/Rules does it:
 * Rule.toObject() on each item, then JSON.stringify() of the Rule objects. A rule is
 * `{condition: [{key, value, comparison}], callback: [{action, metaType?, metaId?, meta?, address?,
 * token?, amount?, comparison?}]}`; unknown keys are dropped and keys come out in that order.
 *
 * Items may be associative arrays or stdClass objects (json_decode without $associative).
 */
class Rule {

  private const CALLBACK_KEYS = [ 'metaType', 'metaId', 'meta', 'address', 'token', 'amount', 'comparison' ];

  /**
   * The JSON a JS client stores in the `rule` meta of an R atom for these rules.
   *
   * @param array $rules
   *
   * @return string
   * @throws \JsonException
   */
  public static function toJson ( array $rules ): string {
    return json_encode( array_map( static fn( mixed $rule ) => self::normalize( $rule ), array_values( $rules ) ),
      JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR );
  }

  /**
   * Validates one rule as JS Rule.toObject() does and returns its JS toJSON() form.
   *
   * @param mixed $rule
   *
   * @return array{condition: array, callback: array}
   */
  public static function normalize ( mixed $rule ): array {
    $condition = self::field( $rule, 'condition' );
    if ( !self::truthy( $condition ) ) {
      throw new MetaMissingException( 'Rule::toObject() - Incorrect rule format! There is no condition field.' );
    }
    $callback = self::field( $rule, 'callback' );
    if ( !self::truthy( $callback ) ) {
      throw new MetaMissingException( 'Rule::toObject() - Incorrect rule format! There is no callback field.' );
    }

    return [
      'condition' => array_map( static fn( mixed $item ) => self::condition( $item ), self::items( $condition ) ),
      'callback' => array_map( static fn( mixed $item ) => self::callback( $item ), self::items( $callback ) ),
    ];
  }

  /**
   * JS Condition.toObject(): key, value and comparison must all be truthy.
   */
  private static function condition ( mixed $item ): array {
    $condition = [
      'key' => self::field( $item, 'key' ),
      'value' => self::field( $item, 'value' ),
      'comparison' => self::field( $item, 'comparison' ),
    ];
    foreach ( $condition as $value ) {
      if ( !self::truthy( $value ) ) {
        throw new RuleArgumentException( 'Condition::constructor( { key, value, comparison } ) - not all class parameters are initialised!' );
      }
    }
    return $condition;
  }

  /**
   * JS Callback.toObject() then toJSON(): `action` is required, the optional keys are kept when truthy.
   */
  private static function callback ( mixed $item ): array {
    $action = self::field( $item, 'action' );
    if ( !self::truthy( $action ) ) {
      throw new RuleArgumentException( 'Callback structure violated, missing mandatory "action" parameter.' );
    }
    $callback = [ 'action' => $action ];
    foreach ( self::CALLBACK_KEYS as $key ) {
      $value = self::field( $item, $key );
      if ( !self::truthy( $value ) ) {
        continue;
      }
      if ( $key === 'amount' && !self::numeric( $value ) ) {
        throw new CodeException( 'Parameter amount should be a string containing numbers' );
      }
      $callback[ $key ] = $key === 'meta' ? self::meta( $value ) : $value;
    }
    return $callback;
  }

  /**
   * JS Meta.toObject(meta).toJSON(): the enumerable own keys of the value, as an object.
   */
  private static function meta ( mixed $value ): stdClass {
    if ( is_string( $value ) ) {
      return (object) mb_str_split( $value );
    }
    if ( is_array( $value ) || $value instanceof stdClass ) {
      return (object) (array) $value;
    }
    return new stdClass();
  }

  /**
   * The elements JS `for...of` visits; anything it cannot iterate is a malformed rule.
   */
  private static function items ( mixed $value ): array {
    if ( is_array( $value ) && array_is_list( $value ) ) {
      return $value;
    }
    if ( is_string( $value ) ) {
      // A string iterates as characters, none of which has a key or an action.
      return mb_str_split( $value );
    }
    throw new RuleArgumentException( 'Rule::toObject() - Incorrect rule format! condition and callback must be lists.' );
  }

  /**
   * `object.<key>` in JS: the property of an object, undefined (null) for anything else.
   */
  private static function field ( mixed $object, string $key ): mixed {
    if ( $object instanceof stdClass ) {
      return $object->$key ?? null;
    }
    if ( is_array( $object ) && !array_is_list( $object ) ) {
      return $object[ $key ] ?? null;
    }
    return null;
  }

  /**
   * JS truthiness: null, false, 0, NaN and "" are false; "0", empty lists and objects are true.
   */
  private static function truthy ( mixed $value ): bool {
    if ( $value === null || $value === false || $value === '' ) {
      return false;
    }
    if ( is_int( $value ) || is_float( $value ) ) {
      return $value != 0 && !is_nan( (float) $value );
    }
    return true;
  }

  /**
   * JS strings.isNumeric(): a number, or a non-blank string that Number() parses.
   */
  private static function numeric ( mixed $value ): bool {
    if ( is_int( $value ) || is_float( $value ) ) {
      return true;
    }
    return is_string( $value ) && trim( $value ) !== '' && is_numeric( trim( $value ) );
  }
}
