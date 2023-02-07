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
use WishKnish\KnishIO\Client\Response\ResponseMolecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationCreateToken
 * @package WishKnish\KnishIO\Client\Query
 */
class MutationCreateToken extends MutationProposeMolecule {

  /**
   * @param Wallet $recipientWallet
   * @param $amount
   * @param array $meta
   *
   * @return $this
   * @throws JsonException
   */
  public function fillMolecule ( Wallet $recipientWallet, $amount, array $meta = [] ): MutationCreateToken {

    // Fill the molecule
    $this->molecule->initTokenCreation( $recipientWallet, $amount, $meta );
    $this->molecule->sign();
    $this->molecule->check();

    return $this;
  }

  /**
   * @param string $response
   *
   * @return ResponseMolecule
   * @throws JsonException
   */
  public function createResponse ( string $response ): ResponseMolecule {
    return new ResponseMolecule( $this, $response );
  }

}
