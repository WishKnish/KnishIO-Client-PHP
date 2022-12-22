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
 * Class ResponseAuthToken
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseAuthToken extends Response {

    /**
     * @var string
     */
    protected string $dataKey = 'data.AuthToken';

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
        return $this->getPayload() !== null;
    }

    /**
     * @return mixed
     * @throws KnishIOException
     */
    public function getPayload (): mixed {
        return $this->data();
    }

    /**
     * @return string|null
     * @throws KnishIOException
     */
    public function token (): ?string {
        return array_get( $this->getPayload(), 'token' );
    }

    /**
     * @return int|null
     * @throws KnishIOException
     */
    public function time (): ?int {
        return array_get( $this->getPayload(), 'time' );
    }

}
