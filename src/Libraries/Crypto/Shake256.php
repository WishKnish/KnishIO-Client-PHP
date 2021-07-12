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

namespace WishKnish\KnishIO\Client\Libraries\Crypto;

use desktopd\SHA3\Sponge as SHA3;
use Exception;

/*

$lengths = [ 32, 64, 1024 ];
for ( $i = 0; $i < 100; $i++ ) {

	$length = array_get( $lengths, random_int(0, count($lengths) - 1) );

	$data_length = random_int( 2048, 4096 );
	$data = \Illuminate\Support\Str::random($data_length);

	$shake_ext = bin2hex( shake256( $data, $length, true ) );
	$shake_php = bin2hex(
			SHA3::init( SHA3::SHAKE256 )
				->absorb( $data )
				->squeeze( $length )
	);


	\PHPUnit\Framework\Assert::assertEquals($shake_php, $shake_ext);
}
dd ('OK');

*/

/**
 * Class Shake256
 * @package WishKnish\KnishIO\Client\Libraries\Crypto
 */
class Shake256 {

  protected static $useExt = true;

  /**
   * @return bool
   */
  public static function usingExt (): bool {
    return static::$useExt && function_exists( 'shake256' );
  }

  /**
   * Shake256 hashing
   *
   * @param $data
   * @param $length
   *
   * @return string
   * @throws Exception
   */
  public static function hash ( $data, $length ) {

    // Using sha3 php extension
    if ( static::usingExt() ) {
      return shake256( $data, $length, true );
    }

    return SHA3::init( SHA3::SHAKE256 )
        ->absorb( $data )
        ->squeeze( $length );

  }

  /**
   * @return SHA3|DesktopdSha3
   * @throws Exception
   */
  public static function init () {
    // Using sha3 php extension
    if ( static::$useExt ) {
      return DesktopdSha3::init( DesktopdSha3::SHAKE256 );
    }

    return SHA3::init( SHA3::SHAKE256 );
  }

}
