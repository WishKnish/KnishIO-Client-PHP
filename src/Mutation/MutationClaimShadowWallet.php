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
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationClaimShadowWallet
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationClaimShadowWallet extends MutationProposeMolecule {

  /**
   * @param string $tokenSlug
   * @param string|null $batchId
   * @param int $otsVersion OTS version of the claimed wallet's key: 1 (default) or 2
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
   */
  public function fillMolecule ( string $tokenSlug, ?string $batchId = null, int $otsVersion = 1 ): MutationClaimShadowWallet {
    // Create a wallet
    $wallet = Wallet::create( $this->molecule->secret(), $tokenSlug, $batchId, otsVersion: $otsVersion );

    // Init shadow wallet claim
    $this->molecule->initShadowWalletClaim( $wallet );
    $this->molecule->sign();
    $this->molecule->check();

    return $this;
  }

}
