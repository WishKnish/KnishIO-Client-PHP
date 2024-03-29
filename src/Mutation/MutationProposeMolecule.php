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

use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationProposeMolecule
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationProposeMolecule extends MutationProposeMoleculeStructure {
  // Molecule
  protected Molecule $molecule;

  // Remainder wallet
  protected Wallet $remainderWallet;

  /**
   * MutationProposeMolecule constructor.
   *
   * @param HttpClientInterface $client
   * @param Molecule $molecule
   * @param string|null $query
   */
  public function __construct ( HttpClientInterface $client, Molecule $molecule, string $query = null ) {
    parent::__construct( $client, $molecule, $query );

    $this->molecule = $molecule;
  }

  /**
   * @return Molecule
   */
  public function molecule (): Molecule {
    return $this->molecule;
  }

  /**
   * @return Wallet
   */
  public function remainderWallet (): Wallet {
    return $this->remainderWallet;
  }

}
