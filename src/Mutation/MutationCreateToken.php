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
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Response\ResponseTokenCreate;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationCreateToken
 * @package WishKnish\KnishIO\Client\Query
 */
class MutationCreateToken extends MutationProposeMolecule {

    /**
     * @param Wallet $recipientWallet
     * @param $amount
     * @param array $metas
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function fillMolecule ( Wallet $recipientWallet, $amount, array $metas = [] ): MutationCreateToken {

        // Fill the molecule
        $this->molecule->initTokenCreation( $recipientWallet, $amount, $metas );
        $this->molecule->sign();
        $this->molecule->check();

        return $this;
    }

    /**
     * Create a response
     *
     * @param string $response
     *
     * @return ResponseTokenCreate
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseTokenCreate {
        return new ResponseTokenCreate( $this, $response );
    }

}
