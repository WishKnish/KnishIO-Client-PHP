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

use SodiumException;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\TokenUnit;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class ResponseWallets
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseWallets extends Response {

    /**
     * @var string
     */
    protected string $dataKey = 'data.Wallets';

    /**
     * @param array $data
     * @param string|null $secret
     *
     * @return Wallet
     * @throws SodiumException
     * @throws KnishIOException
     */
    public static function toClientWallet ( array $data, string $secret = null ): Wallet {

        // Shadow wallet
        if ( $data[ 'walletPosition' ] === null ) {
            $wallet = Wallet::create( $data[ 'bundleHash' ], $data[ 'tokenSlug' ], $data[ 'batchId' ], $data[ 'characters' ] );
        }

        // Regular wallet
        else {
            $wallet = new Wallet( $secret, $data[ 'tokenSlug' ], $data[ 'walletPosition' ], $data[ 'batchId' ], $data[ 'characters' ] );
            $wallet->walletAddress = $data[ 'walletAddress' ];
            $wallet->bundleHash = $data[ 'bundleHash' ];
        }

        // Bind other data
        if ( array_has( $data, 'token' ) && $data[ 'token' ] ) {
            $wallet->tokenName = array_get( $data, 'token.name' );
            $wallet->tokenSupply = array_get( $data, 'token.amount' );
        }
        if ( array_has( $data, 'molecules' ) && $data[ 'molecules' ] !== null ) {
            $wallet->molecules = $data[ 'molecules' ];
        }

        // Get token units from the response
        $tokenUnits = array_get( $data, 'tokenUnits', [] );
        if( $tokenUnits ) {
            foreach ( $tokenUnits as $tokenUnit ) {
                $wallet->tokenUnits[] = TokenUnit::createFromGraphQL( $tokenUnit );
            }
        }

        // Set trade rates
        $tradeRates = array_get( $data, 'tradeRates', [] );
        if( $tradeRates ) {
            foreach ( $tradeRates as $tradeRate ) {
                $wallet->tradeRates[ $tradeRate[ 'tokenSlug' ] ] = $tradeRate[ 'amount' ];
            }
        }

        $wallet->type = $data[ 'type' ];
        $wallet->balance = $data[ 'amount' ] ?: 0;
        $wallet->pubkey = $data[ 'pubkey' ];
        $wallet->createdAt = $data[ 'createdAt' ];

        return $wallet;
    }

    /**
     * @param string|null $secret
     *
     * @return array|null
     * @throws SodiumException
     * @throws KnishIOException
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
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function payload (): ?array {
        return $this->getWallets();
    }

}
