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

/**
 * Class MutationRequestTokens
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationRequestTokens extends MutationProposeMolecule {

    /**
     * @param string $tokenSlug
     * @param $requestedAmount
     * @param string $recipientBundle
     * @param array $metas
     * @param string|null $batchId
     *
     * @return $this
     * @throws JsonException
     */
    public function fillMolecule ( string $tokenSlug, $requestedAmount, string $recipientBundle, array $metas = [], ?string $batchId = null ): MutationRequestTokens {

        // Fill the molecule
        $this->molecule->initTokenRequest( $tokenSlug, $requestedAmount, $recipientBundle, $metas, $batchId );
        $this->molecule->sign();
        $this->molecule->check();

        return $this;
    }

}
