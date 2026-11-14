<?php
/*
                               (
                              (/(
                              (//(
                              (///(
                             (/////(
                             (//////(                          )
                            (////////(                        (/)
                            (////////(                       (///)
                           (//////////(                      (////)
                           (//////////(                     (//////)
                          (////////////(                    (///////)
                         (/////////////(                   (/////////)
                        (//////////////(                  (///////////)
                        (///////////////(                (/////////////)
                       (////////////////(               (//////////////)
                      (((((((((((((((((((              (((((((((((((((
                     (((((((((((((((((((              ((((((((((((((
                     (((((((((((((((((((            ((((((((((((((
                    ((((((((((((((((((((           (((((((((((((
                    ((((((((((((((((((((          ((((((((((((
                    (((((((((((((((((((         ((((((((((((
                    (((((((((((((((((((        ((((((((((
                    ((((((((((((((((((/      (((((((((
                    ((((((((((((((((((     ((((((((
                    (((((((((((((((((    (((((((
                   ((((((((((((((((((  (((((
                   #################  ##
                   ################  #
                  ################# ##
                 %################  ###
                 ###############(   ####
                ###############      ####
               ###############       ######
              %#############(        (#######
             %#############           #########
            ############(              ##########
           ###########                  #############
          #########                      ##############
        %######

        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client;

use JsonException;

/**
 * Class Meta
 * @package WishKnish\KnishIO\Client
 */
class Meta {

  /**
   * Normalizes meta into a list of {key, value} entries with string values, as JS
   * Meta.normalizeMeta does (arrays and objects are JSON-encoded)
   *
   * @param array $meta
   *
   * @return array
   * @throws JsonException
   */
  public static function normalize ( array $meta ): array {
    $result = [];

    foreach ( $meta as $key => $value ) {

      if ( is_array( $value ) && array_key_exists( 'key', $value ) && array_key_exists( 'value', $value ) ) {
        $result[] = [
          'key' => $value[ 'key' ] === null ? null : (string) $value[ 'key' ],
          'value' => self::normalizeValue( $value[ 'value' ] ),
        ];
        continue;
      }

      // Adding normalized meta
      $result[] = [
        'key' => $key,
        'value' => self::normalizeValue( $value ),
      ];
    }
    return $result;
  }

  /**
   * A meta value as JS String() gives it: null stays null, booleans are "true"/"false", numbers take
   * the shortest round-trip form; arrays and objects are compact JSON
   *
   * @param mixed $value
   *
   * @return string|null
   * @throws JsonException
   */
  private static function normalizeValue ( mixed $value ): ?string {
    if ( $value === null || is_string( $value ) ) {
      return $value;
    }
    if ( is_bool( $value ) ) {
      return $value ? 'true' : 'false';
    }
    if ( is_int( $value ) ) {
      return (string) $value;
    }
    if ( is_float( $value ) ) {
      return self::jsFloatString( $value );
    }
    return json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR );
  }

  /**
   * A float as JS String() gives it: the shortest round-trip digits, written out in full below 1e21
   * for integral values (123456789012345680000, not the exact binary 123456789012345683968)
   *
   * @param float $value
   *
   * @return string
   * @throws JsonException
   */
  public static function jsFloatString ( float $value ): string {
    if ( $value == 0 ) {
      return '0';
    }
    $shortest = json_encode( $value, JSON_THROW_ON_ERROR );
    if ( is_finite( $value ) && floor( $value ) === $value && abs( $value ) < 1e21
      && preg_match( '/^(-?)(\d+)(?:\.(\d+))?(?:e\+?(\d+))?$/i', $shortest, $m ) ) {
      $fraction = rtrim( $m[ 3 ] ?? '', '0' );
      $zeros = (int) ( $m[ 4 ] ?? 0 ) - strlen( $fraction );
      return $m[ 1 ] . ltrim( $m[ 2 ] . $fraction, '0' ) . str_repeat( '0', max( 0, $zeros ) );
    }
    return str_replace( '.0e', 'e', $shortest );
  }

  /**
   * @param array $meta
   *
   * @return array
   */
  public static function aggregate ( array $meta ): array {
    $aggregate = [];
    foreach ( $meta as $key => $metaEntry ) {

      if ( is_array( $metaEntry ) && array_key_exists( 'key', $metaEntry ) && array_key_exists( 'value', $metaEntry ) ) {
        $aggregate[ $metaEntry[ 'key' ] ] = $metaEntry[ 'value' ];
        continue;
      }

      $aggregate[ $key ] = $metaEntry;
    }

    return $aggregate;
  }
}
