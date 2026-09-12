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
 * Class StorageOptions
 * @package WishKnish\KnishIO\Client\Storage
 */
class StorageOptions {

  /**
   * @var string|null
   */
  public ?string $label;

  /**
   * @var string|null
   */
  public ?string $passphrase;

  /**
   * @var string|null
   */
  public ?string $recoveryPassphrase = null;

  /**
   * @var bool
   */
  public bool $allowUnrecoverable = false;

  /**
   * StorageOptions constructor.
   *
   * @param string|null $label
   * @param string|null $passphrase
   * @param string|null $recoveryPassphrase
   * @param bool $allowUnrecoverable
   */
  public function __construct (
    ?string $label = null,
    ?string $passphrase = null,
    ?string $recoveryPassphrase = null,
    bool $allowUnrecoverable = false
  ) {
    $this->label = $label;
    $this->passphrase = $passphrase;
    $this->recoveryPassphrase = $recoveryPassphrase;
    $this->allowUnrecoverable = $allowUnrecoverable;
  }
}
