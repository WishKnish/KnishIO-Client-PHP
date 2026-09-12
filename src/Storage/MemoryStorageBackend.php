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

namespace WishKnish\KnishIO\Client\Storage;

/**
 * Class MemoryStorageBackend
 * @package WishKnish\KnishIO\Client\Storage
 */
class MemoryStorageBackend implements StorageBackend {

  /**
   * @var array<string, string>
   */
  protected array $store = [];

  /**
   * @param string $key
   * @return string|null
   */
  public function getItem ( string $key ): ?string {
    return $this->store[ $key ] ?? null;
  }

  /**
   * @param string $key
   * @param string $value
   * @return void
   */
  public function setItem ( string $key, string $value ): void {
    $this->store[ $key ] = $value;
  }

  /**
   * @param string $key
   * @return bool
   */
  public function removeItem ( string $key ): bool {
    if ( !array_key_exists( $key, $this->store ) ) {
      return false;
    }
    unset( $this->store[ $key ] );
    return true;
  }

  /**
   * @return array<int, string>
   */
  public function keys (): array {
    return array_keys( $this->store );
  }

  /**
   * Clear all items from storage
   *
   * @return void
   */
  public function clear (): void {
    $this->store = [];
  }
}
