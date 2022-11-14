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

use JsonException;

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
  public function merge ( array $meta ): self {
    $this->meta = array_merge( $this->meta, $meta );
    return $this;
  }

  /**
   * @param string $context
   *
   * @return $this
   */
  public function addContext ( string $context ): self {
    $this->merge( [ 'context' => $context ] );
    return $this;
  }

  /**
   * @param Wallet $wallet
   *
   * @return $this
   * @throws JsonException
   */
  public function addWallet ( Wallet $wallet ): self {
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
   * Created wallet: used for shadow wallet claim & wallet creation
   *
   * @param Wallet $wallet
   *
   * @return void
   */
  public function addCreatedWallet( Wallet $wallet ): self {
    $this->merge( [
      'walletTokenSlug' => $wallet->token,
      'walletBundleHash' => $wallet->bundle,
      'walletAddress' => $wallet->address,
      'walletPosition' => $wallet->position,
      'walletBatchId' => $wallet->batchId,
      'walletPubkey' => $wallet->pubkey,
      'walletCharacters' => $wallet->characters,
    ] );
    return $this;
  }

  /**
   * @return Wallet
   * @throws \SodiumException
   */
  public function getCreatedWallet(): Wallet {

    /*

    // Token creation
    'walletAddress' => 'address',
    'walletPosition' => 'position',
    'walletPubkey' => 'pubkey',
    'walletCharacters' => 'characters',

    // Wallet creation
    'address' => $newWallet->address,
    'token' => $newWallet->token,
    'bundle' => $newWallet->bundle,
    'position' => $newWallet->position,
    'batch_id' => $newWallet->batchId,
    'pubkey' => $newWallet->pubkey,
    'characters' => $newWallet->characters,

    // Shadow wallet claim
    'tokenSlug' => $tokenSlug,
    'walletAddress' => $wallet->address,
    'walletPosition' => $wallet->position,
    'pubkey' => $wallet->pubkey,
    'characters' => $wallet->characters,
    'batchId' => $wallet->batchId,

    */

    // key - actual key, value - array of the oldest keys
    // @todo this code will be removed, it's tmp supporting
    $walletKeys = [
      'walletTokenSlug' => [ 'token', 'tokenSlug' ],
      'walletBundleHash' => [ 'bundle' ],
      'walletAddress' => [ 'address '],
      'walletPosition' => [ 'position '],
      'walletBatchId' => [ 'batchId', 'batch_id' ],
      'walletPubkey' => [ 'pubkey' ],
      'walletCharacters' => [ 'characters' ],
    ];

    // Set wallet data by all key combinations
    $walletData = [];
    foreach( $walletKeys as $actualKey => $keys ) {
      foreach( array_merge( [ $actualKey ], $keys ) as $key ) {
        $value = array_get( $this->meta, $key );
        if ( $value ) {
          break;
        }
      }
      $walletData[ $actualKey ] = $value;
    }

    // Create a client wallet from the stored data
    $wallet = new Wallet(
      null,
      array_get( $walletData, 'walletTokenSlug' ),
      array_get( $walletData, 'walletPosition' ),
      array_get( $walletData, 'walletBatchId' ) ?: null,
      array_get( $walletData, 'walletCharacters' )
    );
    $wallet->bundle = array_get( $walletData, 'walletBundleHash' );
    $wallet->address = array_get( $walletData, 'walletAddress' );
    $wallet->pubkey = array_get( $walletData, 'walletPubkey' );
    return $wallet;
  }

  /**
   * @param array $policy
   *
   * @return $this
   * @throws JsonException
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
   * @throws JsonException
   */
  public function addSigningWallet ( Wallet $signingWallet ): self {
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
  public function get (): array {
    return $this->meta;
  }
}
