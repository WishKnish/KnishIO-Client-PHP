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
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationClaimShadowWallet
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationClaimShadowWallet extends MutationProposeMolecule {

  /**
   * @param string $tokenSlug
   * @param string|null $batchId
   *
   * @return MutationClaimShadowWallet
   * @throws ReflectionException
   */
  public function fillMolecule ( string $tokenSlug, ?string $batchId = null ) {
    // Create a wallet
    $wallet = Wallet::create( $this->molecule->secret(), $tokenSlug, $batchId );

    // Init shadow wallet claim
    $this->molecule->initShadowWalletClaim( $tokenSlug, $wallet );
    $this->molecule->sign();
    $this->molecule->check();

    return $this;
  }

}
