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

use ArrayObject;
use JetBrains\PhpStorm\ArrayShape;
use JsonException;
use WishKnish\KnishIO\Client\Traits\Json;

/**
 * Class Meta
 * @package WishKnish\KnishIO\Client
 *
 * @property string $modelType
 * @property string $modelId
 * @property array $meta
 * @property $snapshotMolecule
 * @property string $createdAt
 */
class Meta {
  use Json;

  public string $modelType;
  public string $modelId;
  public array $meta;
  public string $snapshotMolecule;
  public string $createdAt;

  /**
   * Meta constructor.
   *
   * @param string $modelType
   * @param string $modelId
   * @param array $meta
   * @param string|null $snapshotMolecule
   */
  public function __construct ( string $modelType, string $modelId, array $meta, string $snapshotMolecule = null ) {
    $this->modelType = $modelType;
    $this->modelId = $modelId;
    $this->meta = $meta;
    $this->snapshotMolecule = $snapshotMolecule;
    $this->createdAt = time();
  }

  /**
   * @param array $meta
   *
   * @return array
   */
  public static function normalizeMeta ( array $meta ): array {
    $result = [];
    foreach ( $meta as $key => $value ) {
      $result[] = is_array( $value ) ? $value : [ 'key' => $key, 'value' => (string) $value, ];
    }
    return $result;
  }

  /**
   * @param array $meta
   *
   * @return array
   */
  public static function aggregateMeta ( array $meta ): array {
    $aggregate = [];
    foreach ( $meta as $metaEntry ) {
      $aggregate[ $metaEntry[ 'key' ] ] = $metaEntry[ 'value' ];
    }
    return $aggregate;
  }

  /**
   * @throws JsonException
   */
  #[ArrayShape( [ 'policy' => "string" ] )] public static function policy ( array $meta, ?array $policy = null ): array {
    $metas = [
      'policy' => []
    ];

    if ( $policy ) {
      foreach ( $policy as $policyKey => $value ) {
        if ( $value && in_array( $policyKey, [ 'read', 'write' ] ) ) {
          foreach ( $value as $key => $content ) {
            $metas[ 'policy' ][ $policyKey ][ $key ] = $content;
          }
        }
      }
    }

    $metas[ 'policy' ] = json_encode( static::defaultPolicy( $metas[ 'policy' ], $meta ), JSON_THROW_ON_ERROR );

    return $metas;
  }

  private static function defaultPolicy( array $policy, array $meta ): array {

    $_policy = ( new ArrayObject( $policy ) )->getArrayCopy();
    $readPolicy = array_filter( $_policy, static fn( $item ) => $item[ 'action' ] === 'read' );
    $writePolicy = array_filter( $_policy, static fn( $item ) => $item[ 'action' ] === 'write' );
    $metaKey = array_keys( $meta );

    foreach ( ['read' => $readPolicy, 'write' => $writePolicy ] as $type => $value ) {
      $policyKey = array_map( static fn ( $item ) => $item[ 'key' ], $value );

      if ( !array_key_exists ( $type, $_policy ) ) {
        $_policy[ $type ] = [];
      }

      foreach ( array_diff( $metaKey, $policyKey ) as $key ) {

        if ( !array_key_exists( $key, $_policy[ $type ] ) ) {
          $_policy[ $type ][ $key ] = ( $type === 'write' && !in_array( $key, [ 'characters', 'pubkey' ] ) ) ?
            [ 'self' ] : [ 'all' ];
        }
      }
    }

    return $_policy;
  }
}
