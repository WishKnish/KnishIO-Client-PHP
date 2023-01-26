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
   * Set all metadata from related wallet to atom
   *
   * @param Wallet $wallet
   *
   * @return $this
   * @throws JsonException
   */
  public function setAtomWallet ( Wallet $wallet ): self {
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
   * Set full NEW wallet metadata
   * Used for shadow wallet claim & wallet creation & token creation
   *
   * @param Wallet $wallet
   *
   * @return $this
   */
  public function setMetaWallet( Wallet $wallet ): self {
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
   * @param bool $shadowWalletClaim
   *
   * @return $this
   */
  public function setShadowWalletClaim( bool $shadowWalletClaim ): self {
    $this->merge( [ 'shadowWalletClaim' => (int) $shadowWalletClaim ] );
    return $this;
  }

  /**
   * (used only on the server side)
   * @return bool
   */
  public function isShadowWalletClaim(): bool {
    return array_get( $this->meta, 'shadowWalletClaim', false );
  }

  /**
   * (used only on the server side)
   * @return Wallet
   * @throws \SodiumException
   */
  public function getMetaWallet(): Wallet {

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
    $wallet->bundle = array_get( $walletData, 'walletBundleHash' );;
    $wallet->address = array_get( $walletData, 'walletAddress' );
    $wallet->pubkey = array_get( $walletData, 'walletPubkey' );
    return $wallet;
  }

  /**
   * @param Wallet $signingWallet
   *
   * @return $this
   * @throws JsonException
   */
  public function setSigningWallet ( Wallet $signingWallet ): self {
    $this->merge( [
      'signingTokenSlug' => $signingWallet->token,
      'signingBundleHash' => $signingWallet->bundle,
      'signingAddress' => $signingWallet->address,
      'signingPosition' => $signingWallet->position,
      'signingPubkey' => $signingWallet->pubkey,
      'signingCharacters' => $signingWallet->characters,
    ] );
    return $this;
  }

  /**
   * (used only on the server side)
   * @return Wallet|null
   * @throws \SodiumException
   */
  public function getSigningWallet(): ?Wallet {

    // Signing wallet key does not found in metas: the value is not set
    if ( !array_has( $this->meta, 'signingBundleHash' ) ) {
      return null;
    }

    // Create a wallet with all existing data
    $wallet = new Wallet(
      null,
      array_get( $this->meta, 'signingTokenSlug' ),
      array_get( $this->meta, 'signingPosition' ),
      null,
      array_get( $this->meta, 'signingCharacters' )
    );
    $wallet->bundle = array_get( $this->meta, 'signingBundleHash' );
    $wallet->address = array_get( $this->meta, 'signingAddress' );
    $wallet->pubkey = array_get( $this->meta, 'signingPubkey' );
    return $wallet;
  }

  /**
   * @return string|null
   */
  public function getCharacters(): ?string {
    return array_get( $this->meta, 'characters' );
  }

  /**
   * @return string|null
   */
  public function getPubkey(): ?string {
    return array_get( $this->meta, 'pubkey' );
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
   * @return array
   */
  public function get (): array {
    return $this->meta;
  }
}
