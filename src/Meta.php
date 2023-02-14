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
        if( is_string( $metas ) ) {
            $metas = json_decode( $metas, true );
        }

        $aggregate = [];
        foreach ( $metas as $metaEntry ) {
            if( is_string( $metaEntry ) ) {
                $metaEntry = json_decode( $metaEntry, true );
            }

            if( is_object( $metaEntry ) ) {
                $metaEntry = (array) $metaEntry;
            }

            try {
                $aggregate[ $metaEntry[ 'key' ] ] = json_decode( $metaEntry[ 'value' ], true, 512, JSON_THROW_ON_ERROR );
            }
            catch (\Throwable){
                $aggregate[ $metaEntry[ 'key' ] ] = $metaEntry[ 'value' ];
            }
        }

        return $aggregate;
    }
}
