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
use WishKnish\KnishIO\Client\Libraries\JsJson;

/**
 *
 */
class PolicyMeta {

  /**
   * @var array
   */
  private array $policy;

  /**
   * @param array $policy
   *
   * @return array
   */
  public static function normalizePolicy ( array $policy = [] ): array {
    $policyMeta = [];
    foreach( $policy as $policyKey => $value ) {
      if ( $value !== null && in_array( $policyKey, [ 'read', 'write' ] ) ) {

        $policyMeta[ $policyKey ] = [];
        foreach( $value as $key => $content ) {
          $policyMeta[ $policyKey ][ $key ] = $content;
        }

      }
    }
    return $policyMeta;
  }

  /**
   * @param array $policy
   * @param array $metaKeys
   */
  public function __construct( array $policy = [], array $metaKeys = [] ) {
    $this->policy = static::normalizePolicy( $policy );
    $this->fillDefault( $metaKeys );
  }

  /**
   * Adds a default entry for every meta key the read/write policy does not cover, as JS
   * PolicyMeta.fillDefault() does (its readPolicy/writePolicy filters always come out empty, so
   * every meta key is a candidate; an existing truthy entry is kept).
   *
   * @param array $metaKeys
   *
   * @return void
   */
  public function fillDefault( array $metaKeys = [] ): void {
    foreach( [ 'read', 'write' ] as $type ) {

      if ( !array_key_exists( $type, $this->policy ) ) {
        $this->policy[ $type ] = [];
      }

      foreach( $metaKeys as $key ) {
        $existing = $this->policy[ $type ][ $key ] ?? null;
        if ( in_array( $existing, [ null, false, '', 0, 0.0 ], true ) ) {
          $this->policy[ $type ][ $key ] = ( $type === 'write' && !in_array( $key, [ 'characters', 'pubkey' ], true ) ) ? [ 'self' ] : [ 'all' ];
        }
      }
    }
  }

  /**
   * @return array
   */
  public function get(): array {
    return $this->policy;
  }

  /**
   * The policy as JS JSON.stringify() writes it: every level an object (`{}` when empty), integer
   * keys first in ascending order as JS objects order them, no escaped slashes or unicode.
   *
   * @return string
   * @throws JsonException
   */
  public function toJson(): string {
    return json_encode( JsJson::object( array_map( static fn( $value ) => is_array( $value ) ? JsJson::object( $value ) : $value, $this->get() ) ),
      JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR );
  }

}
