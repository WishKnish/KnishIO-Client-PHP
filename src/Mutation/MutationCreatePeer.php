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

namespace WishKnish\KnishIO\Client\Mutation;

use ReflectionException;

/**
 * Class MutationCreatePeer
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationCreatePeer extends MutationProposeMolecule {

  /**
   * @param string $slug
   * @param string $host
   * @param string|null $name
   * @param array $cellSlugs
   *
   * @return MutationCreatePeer
   * @throws ReflectionException
   */
  public function fillMolecule ( string $slug, string $host, string $peerId = null, string $name = null, array $cellSlugs = [] ): MutationCreatePeer {
    // Set name as slug if it does not defined
    $name = $name ?: $slug;

    // Fill the molecule
    $this->molecule->initPeerCreation( $slug, $host, $peerId, $name, $cellSlugs );
    $this->molecule->sign();
    $this->molecule->check();

    return $this;
  }

}
