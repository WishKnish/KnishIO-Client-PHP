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

namespace WishKnish\KnishIO\Client\Response;

use Exception;
use WishKnish\KnishIO\Client\TokenUnit;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class ResponseWalletList
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseWalletList extends Response {

  /**
   * @var string
   */
  protected string $dataKey = 'data.Wallet';

  /**
   * @param array $data
   * @param string|null $secret
   *
   * @return Wallet
   * @throws Exception
   */
  public static function toClientWallet ( array $data, string $secret = null ): Wallet {

    // Shadow wallet
    if ( $data[ 'position' ] === null ) {
      $wallet = Wallet::create( $data[ 'bundleHash' ], $data[ 'tokenSlug' ], $data[ 'batchId' ], $data[ 'characters' ] );
    }

    // Regular wallet
    else {
      $wallet = new Wallet( $secret, $data[ 'tokenSlug' ], $data[ 'position' ], $data[ 'batchId' ], $data[ 'characters' ] );
      $wallet->address = $data[ 'address' ];
      $wallet->bundle = $data[ 'bundleHash' ];
    }

    // Bind other data
    if ( array_has( $data, 'token' ) && $data[ 'token' ] ) {
      $wallet->tokenName = array_get( $data, 'token.name' );
      $wallet->tokenSupply = array_get( $data, 'token.amount' );
    }
    if ( array_has( $data, 'molecules' ) ) {
      $wallet->molecules = $data[ 'molecules' ];
    }

    // Get token units from the response
    $tokenUnits = array_get( $data, 'tokenUnits', [] );
    foreach( $tokenUnits as $tokenUnit ) {
      $tokenUnitMetas = array_get( $tokenUnit, 'metas', [] );
      dump( $tokenUnit );
      if ( $tokenUnitMetas ) {
        $tokenUnitMetas = json_decode( $tokenUnitMetas, true );
      }
      $wallet->tokenUnits[] = new TokenUnit(
        array_get( $tokenUnit, 'id' ),
        array_get( $tokenUnit, 'name' ),
        $tokenUnitMetas,
      );
    }

    $wallet->balance = $data[ 'amount' ];
    $wallet->pubkey = $data[ 'pubkey' ];
    $wallet->createdAt = $data[ 'createdAt' ];

    return $wallet;
  }

  /**
   * @param string|null $secret
   *
   * @return array|null
   * @throws Exception
   */
  public function getWallets ( ?string $secret = null ): ?array {
    // Get data
    $list = $this->data();
    if ( !$list ) {
      return [];
    }

    // Get a list of client wallets
    $wallets = [];
    foreach ( $list as $item ) {
      $wallets[] = static::toClientWallet( $item, $secret );
    }

    // Return a wallets list
    return $wallets;
  }

  /**
   * @return array|null
   * @throws Exception
   */
  public function payload (): ?array {
    return $this->getWallets();
  }

}
