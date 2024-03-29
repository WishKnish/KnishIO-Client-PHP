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

use Exception;
use JsonException;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;
use WishKnish\KnishIO\Client\Response\ResponseRequestAuthorization;

/**
 * Class MutationRequestAuthorization
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationRequestAuthorization extends MutationProposeMolecule {

  /**
   * Fill the molecule
   * @throws JsonException
   * @throws Exception
   */
  public function fillMolecule ( array $meta ): MutationRequestAuthorization {
    $this->molecule->initAuthorization( $meta );
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
   */
  public function createResponse ( string $response ): ResponseMolecule {
    return new ResponseRequestAuthorization( $this, $response );
  }
}
