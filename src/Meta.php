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
  public $snapshotMolecule;
  public int $createdAt;

  public function __construct ( $modelType, $modelId, $meta, $snapshotMolecule = null ) {
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
      $result[] = is_array( $value ) ? $value : [ 'key' => $key, 'value' => strval( $value ), ];
    }
    return $result;
  }

  /**
   * @param array|object $meta
   *
   * @return array
   */
  public static function aggregateMeta ( $meta ): array {
    $aggregate = [];
    if ( count( $meta ) ) {
      foreach ( $meta as $metaEntry ) {
        if ( is_object( $metaEntry ) ) {
          $metaKey = $metaEntry->key;
          $metaValue = $metaEntry->value;
        }
        else {
          $metaKey = $metaEntry[ 'key' ];
          $metaValue = $metaEntry[ 'value' ];
        }

        $aggregate[ $metaKey ] = $metaValue;
      }
    }
    return $aggregate;
  }
}
