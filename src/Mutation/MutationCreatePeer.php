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

use JsonException;

/**
 * Class MutationCreatePeer
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationCreatePeer extends MutationProposeMolecule {

    /**
     * @param string $slug
     * @param string $host
     * @param string|null $peerId
     * @param string|null $name
     * @param array $cellSlugs
     *
     * @return $this
     * @throws JsonException
     * @throws \SodiumException
     * @throws \WishKnish\KnishIO\Client\Exception\CryptoException
     * @throws \WishKnish\KnishIO\Client\Exception\MetaMissingException
     * @throws \WishKnish\KnishIO\Client\Exception\MoleculeAtomIndexException
     * @throws \WishKnish\KnishIO\Client\Exception\MoleculeAtomsMissingException
     * @throws \WishKnish\KnishIO\Client\Exception\MoleculeHashMismatchException
     * @throws \WishKnish\KnishIO\Client\Exception\MoleculeHashMissingException
     * @throws \WishKnish\KnishIO\Client\Exception\MoleculeSignatureMalformedException
     * @throws \WishKnish\KnishIO\Client\Exception\MoleculeSignatureMismatchException
     * @throws \WishKnish\KnishIO\Client\Exception\TokenTypeException
     * @throws \WishKnish\KnishIO\Client\Exception\TransferBalanceException
     * @throws \WishKnish\KnishIO\Client\Exception\TransferMalformedException
     * @throws \WishKnish\KnishIO\Client\Exception\TransferMismatchedException
     * @throws \WishKnish\KnishIO\Client\Exception\TransferRemainderException
     * @throws \WishKnish\KnishIO\Client\Exception\TransferToSelfException
     * @throws \WishKnish\KnishIO\Client\Exception\TransferUnbalancedException
     * @throws \WishKnish\KnishIO\Client\Exception\TransferWalletException
     * @throws \WishKnish\KnishIO\Client\Exception\WalletBatchException
     * @throws \WishKnish\KnishIO\Client\Exception\WalletSignatureException
     */
    public function fillMolecule ( string $slug, string $host, string $peerId = null, string $name = null, array $cellSlugs = [] ): MutationCreatePeer {
        // Set name as slug if it is not defined
        $name = $name ?: $slug;

        // Fill the molecule
        $this->molecule->initPeerCreation( $slug, $host, $peerId, $name, $cellSlugs );
        $this->molecule->sign();
        $this->molecule->check();

        return $this;
    }

}
