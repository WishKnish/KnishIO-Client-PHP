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
     * @param array $meta
     *
     * @return array
     * @throws JsonException
     */
    public static function normalize ( array $meta ): array {
        $result = [];

        foreach ( $meta as $key => $value ) {

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
     * @param array $meta
     *
     * @return array
     */
    public static function aggregate ( array $meta ): array {
        $aggregate = [];
        foreach ( $meta as $metaEntry ) {
            $aggregate[ $metaEntry[ 'key' ] ] = $metaEntry[ 'value' ];
        }
        return $aggregate;
    }
}
