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
use WishKnish\KnishIO\Client\Exception\TransferMalformedException;
use WishKnish\KnishIO\Client\Exception\TransferMismatchedException;
use WishKnish\KnishIO\Client\Exception\TransferRemainderException;
use WishKnish\KnishIO\Client\Exception\TransferToSelfException;
use WishKnish\KnishIO\Client\Exception\TransferUnbalancedException;
use WishKnish\KnishIO\Client\Exception\TransferWalletException;
use WishKnish\KnishIO\Client\Exception\WalletBatchException;
use WishKnish\KnishIO\Client\Exception\WalletSignatureException;
use WishKnish\KnishIO\Client\Response\ResponseMetaCreate;

/**
 * Class MutationCreateMeta
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationCreateMeta extends MutationProposeMolecule {

    /**
     * @param string $metaType
     * @param string $metaId
     * @param array $metadata
     *
     * @return void
     * @throws JsonException
     */
    /**
     * @param string $metaType
     * @param string $metaId
     * @param array $metadata
     *
     * @return void
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
     * @throws TransferMalformedException
     * @throws TransferMismatchedException
     * @throws TransferRemainderException
     * @throws TransferToSelfException
     * @throws TransferUnbalancedException
     * @throws TransferWalletException
     * @throws WalletBatchException
     * @throws WalletSignatureException
     */
    public function fillMolecule ( string $metaType, string $metaId, array $metadata ): void {
        $this->molecule->initMeta( $metadata, $metaType, $metaId );
        $this->molecule->sign();
        $this->molecule->check();
    }

    /**
     * @param $response
     *
     * @return ResponseMetaCreate
     * @throws JsonException
     * @throws \WishKnish\KnishIO\Client\Exception\InvalidResponseException
     * @throws \WishKnish\KnishIO\Client\Exception\UnauthenticatedException
     */
    public function createResponse ( $response ): ResponseMetaCreate {
        return new ResponseMetaCreate( $this, $response );
    }

}
