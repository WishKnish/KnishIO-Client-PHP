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
   * @return $this
   * @throws \JsonException
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
   * @param array $policy
   *
   * @return $this
   * @throws \JsonException
   */
  public function addPolicy( array $policy ): self {

    $policyMeta = new PolicyMeta( $policy, array_keys( $this->meta ) );

    $this->merge( [
      'policy' => $policyMeta->toJson(),
    ] );

    return $this;
  }

  /**
   * @param Wallet $signingWallet
   *
   * @return $this
   * @throws \JsonException
   */
  public function addSigningWallet( Wallet $signingWallet ): self {
    $this->merge( [
      'signingWallet' => json_encode( [
        'address' => $signingWallet->address,
        'position' => $signingWallet->position,
        'pubkey' => $signingWallet->pubkey,
        'characters' => $signingWallet->characters,
      ], JSON_THROW_ON_ERROR ),
    ] );
    return $this;
  }

  /**
   * @return array
   */
  public function get(): array {
    return $this->meta;
  }
}
