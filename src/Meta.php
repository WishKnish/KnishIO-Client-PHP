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
 * Class Meta
 * @package WishKnish\KnishIO\Client
 */
class Meta {

    /**
     * @param array $metas
     *
     * @return array
     * @throws JsonException
     */
    public static function normalize ( array $metas ): array {

        // Already normalized?
        if ( array_is_list( $metas ) ) {
            return $metas;
        }

        $result = [];

        foreach ( $metas as $key => $value ) {

            // Handling non-string meta values
            if ( !is_string( $value ) ) {

                // Is value numeric?
                if ( is_numeric( $value ) ) {
                    $value = (string) $value;
                }

                // Is value an object?
                if ( is_object( $value ) || is_array( $value ) ) {
                    $value = json_encode( $value, JSON_THROW_ON_ERROR );
                }
            }

            // Adding normalized meta
            $result[] = [
                'key' => $key,
                'value' => $value,
            ];
        }
        return $result;
    }

    /**
     * @param array|string $metas
     *
     * @return array
     */
    public static function aggregate ( array|string $metas ): array {

        // Handling stringified metas
        if( gettype( $metas) === 'string') {
            $metas = json_decode( $metas, true );
        }

        $aggregate = [];
        foreach ( $metas as $metaEntry ) {
            if( gettype( $metaEntry) === 'string') {
                $metaEntry = json_decode( $metaEntry, true );
            }

            if( gettype( $metaEntry ) === 'object' ) {
                $metaEntry = (array) $metaEntry;
            }

            $aggregate[ $metaEntry[ 'key' ] ] = $metaEntry[ 'value' ];
        }

        return $aggregate;
    }
}
