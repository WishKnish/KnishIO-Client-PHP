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

if ( !function_exists( 'array_unpacking' ) ) {

  /**
   * @param array $arr
   * @param string|integer ...$args
   *
   * @return array
   */
  function array_unpacking ( array $arr, ...$args ): array {

    foreach ( $args as $value ) {

      if ( !is_string( $value ) && !is_int( $value ) ) {

        throw new InvalidArgumentException( 'All arguments except the first must be either an integer or a string.' );

      }

    }

    $new = array_intersect_key( $arr, array_flip( $args ) );

    return array_map( static function ( $item ) use ( $new ) {

      return $new[ $item ] ?? null;

    }, $args );

  }

}

if ( !function_exists( 'array_has' ) ) {
  /**
   * Check if an item or items exist in an array using "dot" notation.
   *
   * @param ArrayAccess|array|null $array
   * @param array|string $keys
   *
   * @return bool
   */
  function array_has ( ArrayAccess|array|null $array, array|string $keys ): bool {
    if ( !is_array( $array ) ) {
      return false;
    }
    $keys = (array) $keys;
    foreach ( $keys as $key ) {
      $_keys = explode( '.', $key );
      $_array = $array;
      foreach ( $_keys as $_key ) {
        if ( !array_key_exists( $_key, $_array ) ) {
          return false;
        }
        $_array = $_array[ $_key ];
      }
    }
    return true;
  }
}

if ( !function_exists( 'array_get' ) ) {

  /**
   * Get an item from an array using "dot" notation.
   *
   * @param ArrayAccess|array|null $array
   * @param string $keys
   * @param mixed|null $default
   *
   * @return mixed
   */
  function array_get ( ArrayAccess|array|null $array, string $keys, mixed $default = null ): mixed {
    foreach ( explode( '.', $keys ) as $key ) {
      if ( !array_has( $array, $key ) ) {
        return $default;
      }
      $array = $array[ $key ];
    }
    return $array;
  }
}

if ( !function_exists( 'array_every' ) ) {

  /**
   * @param array $array
   * @param callable $callable
   *
   * @return bool
   */
  function array_every( array $array, callable $callable ): bool {
    foreach ( $array as $value ) {
      if ( !$callable( $value ) ) {
        return false;
      }
    }
    return true;
  }
}
