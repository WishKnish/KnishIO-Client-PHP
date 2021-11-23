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

if ( !function_exists( 'default_if_null' ) ) {

  /**
   * Get a default value if the passed value is null
   *
   * @param $value
   * @param $default
   *
   * @return mixed
   */
  function default_if_null ( $value, $default ) {
    return $value ?? $default;
  }

}

if ( !function_exists( 'array_has' ) ) {
  /**
   * Check if an item or items exist in an array using "dot" notation.
   *
   * @param ArrayAccess|array $array
   * @param string|array $keys
   *
   * @return bool
   */
  function array_has ( $array, $keys ): bool {
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
   * @param ArrayAccess|array $array
   * @param string $keys
   * @param mixed $default
   *
   * @return mixed
   */
  function array_get ( $array, string $keys, $default = null ) {
    $expKeys = explode( '.', $keys );
    foreach ( $expKeys as $key ) {
      if ( !array_has( $array, $key ) ) {
        return $default;
      }
      $array = $array[ $key ];
    }
    return $array;
  }
}
