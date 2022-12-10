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
use SodiumException;
use WishKnish\KnishIO\Client\Exception\CryptoException;
use WishKnish\KnishIO\Client\Exception\MetaMissingException;
use WishKnish\KnishIO\Client\Exception\MoleculeAtomIndexException;
use WishKnish\KnishIO\Client\Exception\MoleculeAtomsMissingException;
use WishKnish\KnishIO\Client\Exception\MoleculeHashMismatchException;
use WishKnish\KnishIO\Client\Exception\MoleculeHashMissingException;
use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMalformedException;
use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMismatchException;
use WishKnish\KnishIO\Client\Exception\TokenTypeException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\TransferMalformedException;
use WishKnish\KnishIO\Client\Exception\TransferMismatchedException;
use WishKnish\KnishIO\Client\Exception\TransferRemainderException;
use WishKnish\KnishIO\Client\Exception\TransferToSelfException;
use WishKnish\KnishIO\Client\Exception\TransferUnbalancedException;
use WishKnish\KnishIO\Client\Exception\TransferWalletException;
use WishKnish\KnishIO\Client\Exception\WalletBatchException;
use WishKnish\KnishIO\Client\Exception\WalletSignatureException;

/**
 * Class MutationCreateIdentifier
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationCreateIdentifier extends MutationProposeMolecule {

    /**
     * @param $type
     * @param $contact
     * @param $code
     *
     * @return MutationCreateIdentifier
     * @throws JsonException
     * @throws SodiumException
     */
    /**
     * @param $type
     * @param $contact
     * @param $code
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws CryptoException
     * @throws MetaMissingException
     * @throws MoleculeAtomIndexException
     * @throws MoleculeAtomsMissingException
     * @throws MoleculeHashMismatchException
     * @throws MoleculeHashMissingException
     * @throws MoleculeSignatureMalformedException
     * @throws MoleculeSignatureMismatchException
     * @throws TokenTypeException
     * @throws TransferBalanceException
     * @throws TransferMalformedException
     * @throws TransferMismatchedException
     * @throws TransferRemainderException
     * @throws TransferToSelfException
     * @throws TransferUnbalancedException
     * @throws TransferWalletException
     * @throws WalletBatchException
     * @throws WalletSignatureException
     */
    public function fillMolecule ( $type, $contact, $code ): MutationCreateIdentifier {
        $this->molecule->initIdentifierCreation( $type, $contact, $code );
        $this->molecule->sign();
        $this->molecule->check();

        return $this;
    }

}
