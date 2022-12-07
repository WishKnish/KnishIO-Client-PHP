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

namespace WishKnish\KnishIO\Client\Exception;

use JetBrains\PhpStorm\Pure;
use LogicException;
use Throwable;

/**
 * Class KnishIOException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
abstract class KnishIOException extends LogicException implements BaseExceptionInterface {
  protected $message = 'Unknown exception';
  protected $code = 0;

  /**
   * KnishIOException constructor.
   *
   * @param string $message
   * @param int $code
   * @param Throwable|null $previous
   *
   * @noinspection PhpPureAttributeCanBeAddedInspection
   */
  public function __construct ( string $message, int $code = 0, Throwable $previous = null ) {
    parent::__construct( $message, $code, $previous );
  }

  /**
   * @return string
   */
  #[Pure]
  public function __toString (): string {
    return static::class . " '" . $this->message . "' in " . $this->file . ' (' . $this->line . ')' . PHP_EOL . $this->getTraceAsString();
  }
}
