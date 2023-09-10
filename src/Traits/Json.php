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

namespace WishKnish\KnishIO\Client\Traits;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Trait Json
 * @package WishKnish\KnishIO\Client\Traits
 */
trait Json {
    /**
     * @param array $data
     * @param null $object
     *
     * @return static
     */
    public static function arrayToObject ( array $data, $object = null ): static {
        $object = $object ?? new static();
        foreach ( $data as $property => $value ) {

            // Has a setProperty customization function
            if ( method_exists( $object, 'setProperty' ) ) {
                $object->setProperty( $property, $value );
            }

            // Default property set
            else {
                $object->$property = $value;
            }

        }
        return $object;
    }

    /**
     * @param string $string
     *
     * @return array|object
     */
    public static function jsonToObject ( string $string ): object|array {
        return ( new Serializer( [ new ObjectNormalizer(), ], [ new JsonEncoder(), ] ) )->deserialize( $string, static::class, 'json' );
    }

    /**
     * @return string
     */
    public function toJson (): string {
        return ( new Serializer( [ new ObjectNormalizer(), ], [ new JsonEncoder(), ] ) )->serialize( $this, 'json' );
    }
}
