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
use WishKnish\KnishIO\Client\Response\ResponseCreateRule;

/**
 * Mutation for creating a new rule attached to some MetaType
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationCreateRule extends MutationProposeMolecule {

  /**
   * Fill the molecule with rule creation data
   *
   * @param string $metaType
   * @param string $metaId
   * @param array $rule
   * @param array $policy
   *
   * @return void
   * @throws JsonException
   * @throws SodiumException
   */
  public function fillMolecule(string $metaType, string $metaId, array $rule, array $policy = []): void {
    $this->molecule->createRule($metaType, $metaId, $rule, $policy);
    $this->molecule->sign();
    $this->molecule->check();
  }

  /**
   * @param $response
   *
   * @return ResponseCreateRule
   * @throws JsonException
   */
  public function createResponse($response): ResponseCreateRule {
    return new ResponseCreateRule($this, $response);
  }
}