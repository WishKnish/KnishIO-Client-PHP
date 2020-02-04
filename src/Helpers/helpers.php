<?php

if ( ! \function_exists( 'array_unpacking' ) ) {

    /**
     * @param array $arr
     * @param string|integer ...$args
     * @return array
     */
    function array_unpacking ( array $arr, ...$args )
    {

        foreach ( $args as $value ) {

            if ( ! \is_string( $value ) && ! \is_integer( $value ) ) {

                throw new \InvalidArgumentException( 'All arguments except the first must be either an integer or a string.' );

            }

        }

        $new = \array_intersect_key( $arr, \array_flip( $args ) );

        return \array_map( static function ( $item ) use ( $new ) {

            return \key_exists( $item, $new ) ? $new[ $item ] : null;

        }, $args );

    }

}
