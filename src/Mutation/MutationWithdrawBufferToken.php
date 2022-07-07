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
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationWithdrawBufferToken
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationWithdrawBufferToken extends MutationProposeMolecule {

  /**
   * @param float $amount
   * @param Wallet $recipientWallet
   * @param Wallet|null $signingWallet
   *
   * @return $this
   * @throws JsonException
   */
  public function fillMolecule ( float $amount, Wallet $recipientWallet, ?Wallet $signingWallet = null ): self {
    $this->molecule->initWithdrawBuffer( $amount, [ $recipientWallet ], $signingWallet );
    $this->molecule->sign();
    $this->molecule->check( $this->molecule->sourceWallet() );

    return $this;
  }

}
