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

use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseTokenCreate;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationCreatePeer
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationCreatePeer extends MutationProposeMolecule {

  /**
   * @param $recipientWallet
   * @param $amount
   * @param array $metas
   *
   * @throws \ReflectionException
   * @throws \Exception
   */
  public function fillMolecule ( string $slug, string $host, string $name = null, array $cellSlugs = [] ) {
    // Set name as slug if it does not defined
    $name = $name ?: $slug;

    // Fill the molecule
    $this->molecule->initPeerCreation( $slug, $host, $name, $cellSlugs );
    $this->molecule->sign();
    $this->molecule->check();

    return $this;
  }

}
