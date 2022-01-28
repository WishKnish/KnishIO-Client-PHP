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

use Exception;
use JsonException;
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
   * @param array|null $meta
   *
   * @return MutationCreateToken
   * @throws JsonException
   * @throws Exception
   */
  public function fillMolecule ( Wallet $recipientWallet, $amount, array $meta = null ): MutationCreateToken {
    // Default metas value
    $meta = default_if_null( $meta, [] );

    // Fill the molecule
    $this->molecule->initTokenCreation( $recipientWallet, $amount, $meta );
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
   * @throws JsonException
   */
  public function createResponse ( string $response ): ResponseTokenCreate {
    return new ResponseTokenCreate( $this, $response );
  }

}
