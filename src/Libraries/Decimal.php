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

namespace WishKnish\KnishIO\Client\Libraries;

/*
$v1 = 1.0/(Decimal::$multiplier);
$v2 = 2.0/(Decimal::$multiplier);
$v10 = 10.0/(Decimal::$multiplier);
dd([
	[$v1, $v1, Decimal::cmp($v1, $v1)],
	[$v2, $v2, Decimal::cmp($v2, $v2)],
	[$v1, $v2, Decimal::cmp($v1, $v2)],
	[$v2, $v1, Decimal::cmp($v2, $v1)],
	[$v1, $v10, Decimal::cmp($v1, $v10)],
	[$v2, $v10, Decimal::cmp($v2, $v10)],
	[$v10, $v1, Decimal::cmp($v10, $v1)],
	[$v10, $v2, Decimal::cmp($v10, $v2)],
]);
*/

/**
 * Class Decimal
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Decimal {

  /**
   * @var int
   */
  public static int $precision = 18;

  /**
   * @return float
   */
  public static function multiplier (): float {
    return 10 ** static::$precision;
  }

  /**
   * @param float $val
   *
   * @return float
   */
  public static function val ( float $val ): float {
    if ( abs( $val * static::multiplier() ) < 1 ) {
      return 0;
    }
    return $val;
  }

  /**
   * Compare decimal with precision
   *
   * @param float $val1
   * @param float $val2
   *
   * @return int
   */
  public static function cmp ( float $val1, float $val2 ): int {
    $val1 = static::val( $val1 ) * static::multiplier();
    $val2 = static::val( $val2 ) * static::multiplier();

    // Equal
    if ( abs( $val1 - $val2 ) < 1 ) {
      return 0;
    }

    // Greater or smaller
    return ( $val1 > $val2 ) ? 1 : -1;
  }

  /**
   * @param float $val1
   * @param float $val2
   *
   * @return bool
   */
  public static function equal ( float $val1, float $val2 ): bool {
    return ( static::cmp( $val1, $val2 ) === 0 );
  }

}
