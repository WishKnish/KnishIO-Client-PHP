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

use ReflectionException;
use WishKnish\KnishIO\Client\Response\ResponseMetaCreate;

/**
 * Class MutationCreateMeta
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationCreateMeta extends MutationProposeMolecule {

  /**
   * @param string $metaType
   * @param string $metaId
   * @param array $metadata
   *
   * @throws ReflectionException
   */
  public function fillMolecule ( string $metaType, string $metaId, array $metadata ): void {
    $this->molecule->initMeta( $metadata, $metaType, $metaId );
    $this->molecule->sign();
    $this->molecule->check();
  }

  /**
   * @param $response
   *
   * @return ResponseMetaCreate
   */
  public function createResponse ( $response ): ResponseMetaCreate {
    return new ResponseMetaCreate( $this, $response );
  }

}
