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
class PolicyMeta {

    /**
     * @var array
     */
    private array $policy;

    /**
     * @param array $policy
     * @param array $metaKeys
     */
    public function __construct ( array $policy = [], array $metaKeys = [] ) {
        $this->policy = static::normalizePolicy( $policy );
        $this->fillDefault( $metaKeys );
    }

    /**
     * @param array $policy
     *
     * @return array
     */
    public static function normalizePolicy ( array $policy = [] ): array {
        $policyMeta = [];
        foreach ( $policy as $policyKey => $value ) {
            if ( $value !== null && in_array( $policyKey, [ 'read', 'write' ] ) ) {

                $policyMeta[ $policyKey ] = [];
                foreach ( $value as $key => $content ) {
                    $policyMeta[ $policyKey ][ $key ] = $content;
                }

            }
        }
        return $policyMeta;
    }

    /**
     * @param array $metaKeys
     *
     * @return void
     */
    public function fillDefault ( array $metaKeys = [] ): void {
        $readPolicy = array_filter( $this->policy,
            static function ( $item ) {
                return $item[ 'action' ] === 'read';
            }
        );
        $writePolicy = array_filter( $this->policy,
            static function ( $item ) {
                return $item[ 'action' ] === 'write';
            }
        );
        foreach ( [ 'read' => $readPolicy, 'write' => $writePolicy ] as $type => $value ) {
            $policyKeys = array_column( $value, 'key' );

            if ( !array_has( $this->policy, $type ) ) {
                $this->policy[ $type ] = [];
            }

            foreach ( array_diff( $metaKeys, $policyKeys ) as $key ) {
                if ( !array_get( $this->policy[ $type ], $key ) ) {
                    $this->policy[ $type ][ $key ] = ( $type === 'write' && !in_array( $key, [ 'characters', 'pubkey' ] ) ) ? [ 'self' ] : [ 'all' ];
                }
            }
        }
    }

    /**
     * @return array
     */
    public function get (): array {
        return $this->policy;
    }

    /**
     * @return string
     * @throws JsonException
     */
    public function toJson (): string {
        return json_encode( $this->get(), JSON_THROW_ON_ERROR );
    }

}
