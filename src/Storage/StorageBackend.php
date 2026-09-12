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
 * Interface StorageBackend
 * @package WishKnish\KnishIO\Client\Storage
 */
interface StorageBackend {

  /**
   * Retrieve an item by key
   *
   * @param string $key
   * @return string|null
   */
  public function getItem ( string $key ): ?string;

  /**
   * Set an item by key
   *
   * @param string $key
   * @param string $value
   * @return void
   */
  public function setItem ( string $key, string $value ): void;

  /**
   * Remove an item by key
   *
   * @param string $key
   * @return bool True if item was removed, false if it did not exist
   */
  public function removeItem ( string $key ): bool;

  /**
   * List all stored keys
   *
   * @return array<int, string>
   */
  public function keys (): array;
}
