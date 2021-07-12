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
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class ResponseWalletList
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseWalletList extends Response {
  protected $dataKey = 'data.Wallet';

  /**
   * @param array $data
   *
   * @throws Exception
   */
  public static function toClientWallet ( array $data, string $secret = null ) {

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
    $wallet->tokenUnits = array_get( $data, 'tokenUnits', [] );

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
  public function getWallets ( ?string $secret = null ) {
    // Get data
    $list = $this->data();
    if ( !$list ) {
      return null;
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
  public function payload () {
    // Get data
    $list = $this->data();
    if ( !$list ) {
      return null;
    }

    // Get a list of client wallets
    $wallets = [];
    foreach ( $list as $item ) {
      $wallets[] = static::toClientWallet( $item );
    }

    // Return a wallets list
    return $wallets;
  }

}
