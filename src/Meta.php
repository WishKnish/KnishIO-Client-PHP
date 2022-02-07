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
}
