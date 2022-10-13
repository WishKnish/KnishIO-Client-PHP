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

namespace WishKnish\KnishIO\Client;

/**
 *
 */
class AtomMeta {

  /**
   * @param array $meta
   */
  public function __construct(
    private array $meta = [],
  ) {

  }

  /**
   * @param array $meta
   *
   * @return $this
   */
  public function merge( array $meta ): self {
    $this->meta = array_merge( $this->meta, $meta );
    return $this;
  }

  /**
   * @param string $context
   *
   * @return $this
   */
  public function addContext( string $context ): self {
    $this->merge( [ 'context' => $context ] );
    return $this;
  }

  /**
   * @param Wallet $wallet
   *
   * @return void
   */
  public function addWallet( Wallet $wallet ): self {
    $walletMeta = [
      'pubkey' => $wallet->pubkey,
      'characters' => $wallet->characters,
    ];
    if ( $wallet->tokenUnits ) {
      $walletMeta[ 'tokenUnits' ] = json_encode( $wallet->getTokenUnitsData(), JSON_THROW_ON_ERROR );
    }
    if ( $wallet->tradeRates ) {
      $walletMeta[ 'tradeRates' ] = json_encode( $wallet->tradeRates, JSON_THROW_ON_ERROR );
    }
    $this->merge( $walletMeta );
    return $this;
  }

  /**
   * @return array
   */
  public function get(): array {
    return $this->meta;
  }
}
