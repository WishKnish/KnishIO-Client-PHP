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

namespace WishKnish\KnishIO\Client\Mutation;

use JsonException;
use SodiumException;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;
use WishKnish\KnishIO\Client\Response\ResponseRequestAuthorization;

/**
 * Class MutationRequestAuthorization
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationRequestAuthorization extends MutationProposeMolecule {

    /**
     * Fill the molecule
     *
     * @param array $metas
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function fillMolecule ( array $metas ): MutationRequestAuthorization {
        $this->molecule->initAuthorization( $metas );
        $this->molecule->sign();
        $this->molecule->check();
        return $this;
    }

    /**
     * Create a response
     *
     * @param string $response
     *
     * @return ResponseMolecule
     * @throws JsonException
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseMolecule {
        return new ResponseRequestAuthorization( $this, $response );
    }
}
