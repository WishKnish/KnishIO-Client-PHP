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

namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Exception\KnishIOException;

/**
 * Class ResponseAccessToken
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseAccessToken extends Response {

    /**
     * @var string
     */
    protected string $dataKey = 'data.AccessToken';

    /**
     * @return string
     */
    public function reason (): string {
        return 'Invalid response from server';
    }

    /**
     * @return bool
     * @throws KnishIOException
     */
    public function success (): bool {
        return $this->payload() !== null;
    }

    /**
     * @return mixed
     * @throws KnishIOException
     */
    public function payload (): mixed {
        return $this->data();
    }

    /**
     * @return string|null
     * @throws KnishIOException
     */
    public function token (): ?string {
        return array_get( $this->payload(), 'token' );
    }

    /**
     * @return int|null
     * @throws KnishIOException
     */
    public function time (): ?int {
        return array_get( $this->payload(), 'time' );
    }

}
