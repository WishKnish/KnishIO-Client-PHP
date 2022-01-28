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

/**
 * Class MutationRequestTokens
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationRequestTokens extends MutationProposeMolecule {

  /**
   * @param string $tokenSlug
   * @param $requestedAmount
   * @param string $metaType
   * @param string $metaId
   * @param array|null $metas
   * @param string|null $batchId
   *
   * @return MutationRequestTokens
   * @throws JsonException|Exception
   */
  public function fillMolecule ( string $tokenSlug, $requestedAmount, string $metaType, string $metaId, array $metas = null, ?string $batchId = null ): MutationRequestTokens {
    // Default metas value
    $metas = default_if_null( $metas, [] );

    // Fill the molecule
    $this->molecule->initTokenRequest( $tokenSlug, $requestedAmount, $metaType, $metaId, $metas, $batchId );
    $this->molecule->sign();
    $this->molecule->check();

    return $this;
  }

}
