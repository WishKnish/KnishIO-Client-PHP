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

use JsonException;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\MoleculeStructure;

/**
 * Class ResponseMoleculeList
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseMoleculeList extends Response {

    /**
     * @var string
     */
    protected string $dataKey = 'data.Molecule';

    /**
     * @param array $data
     *
     * @return MoleculeStructure
     * @throws JsonException
     */
    public static function toClientMolecule ( array $data ): MoleculeStructure {
        return MoleculeStructure::toObject( $data );
    }

    /**
     * @return array
     * @throws KnishIOException
     * @throws JsonException
     */
    public function payload (): array {
        // Get data
        $list = $this->data();

        // Get a list of client molecules
        $molecules = [];
        foreach ( $list as $item ) {
            $molecules[] = static::toClientMolecule( $item );
        }

        // Return a molecules list
        return $molecules;
    }

}
