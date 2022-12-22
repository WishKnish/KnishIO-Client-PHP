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
     * @param array $metas
     */
    public function __construct (
        private array $metas = [],
    ) {

    }

    /**
     * @param array $metas
     *
     * @return $this
     */
    public function merge ( array $metas ): self {
        $this->metas = array_merge( $this->metas, $metas );
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
        $walletMetas = [
            'pubkey' => $wallet->pubkey,
            'characters' => $wallet->characters,
        ];
        if ( $wallet->tokenUnits ) {
            $walletMetas[ 'tokenUnits' ] = json_encode( $wallet->getTokenUnitsData(), JSON_THROW_ON_ERROR );
        }
        if ( $wallet->tradeRates ) {
            $walletMetas[ 'tradeRates' ] = json_encode( $wallet->tradeRates, JSON_THROW_ON_ERROR );
        }
        $this->merge( $walletMetas );
        return $this;
    }

    /**
     * @param array $policy
     *
     * @return $this
     * @throws JsonException
     */
    public function addPolicy ( array $policy ): self {

        $policyMeta = new PolicyMeta( $policy, array_keys( $this->metas ) );

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
        return $this->metas;
    }
}
