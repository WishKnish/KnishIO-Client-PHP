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

use WishKnish\KnishIO\Client\Meta;
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
   */
  public static function toClientMolecule ( array $data ): MoleculeStructure {

    $data[ 'bundle' ] = $data[ 'bundleHash' ];
    unset( $data[ 'bundleHash' ] );

    foreach( $data[ 'atoms' ] as $key => $atom ) {
      $atom[ 'token' ] = $atom[ 'tokenSlug' ];
      $atom[ 'meta' ] = Meta::normalize( json_decode( $atom[ 'metasJson' ], true ) );
      unset( $atom[ 'tokenSlug' ] );
      unset( $atom[ 'metasJson' ] );

      $data[ 'atoms' ][ $key ] = $atom;
    }

    return MoleculeStructure::toObject( $data );
  }

  /**
   * @return array
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
