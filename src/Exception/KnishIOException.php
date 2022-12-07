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

use Exception;
use Throwable;

/**
 * Class KnishIOException
 * @package WishKnish\KnishIO\Client\Exception
 */
class KnishIOException extends Exception {

    protected $payload;

    /**
     * KnishIOException constructor.
     *
     * @param string $message
     * @param null $payload
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct ( string $message = 'A KnishIO Server exception has been triggered.', $payload = null, int $code = 0, Throwable $previous = null ) {
        parent::__construct( $message, $code, $previous );

        $this->payload = $payload;
    }

    /**
     * @return null
     */
    public function getPayload () {
        return $this->payload;
    }

    /**
     * @return string
     */
    public function __toString (): string {
        return static::class . " '" . $this->message . "' in " . $this->file . ' (' . $this->line . ')' . PHP_EOL . $this->getTraceAsString();
    }
}
