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

/**
 * Class ResponseMetaTypeViaAtom
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseMetaTypeViaAtom extends Response {

  /**
   * @var string
   */
  protected string $dataKey = 'data.MetaTypeViaAtom';

  /**
   * @return array|array[]
   */
  public function payload (): array {
    $metaTypeData = $this->data();
    
    if ( !$metaTypeData || empty( $metaTypeData ) ) {
      return [
        'instances' => [],
        'instanceCount' => [],
        'paginatorInfo' => []
      ];
    }

    $response = [
      'instances' => [],
      'instanceCount' => [],
      'paginatorInfo' => []
    ];

    // Get the last meta type data (matching JS SDK behavior)
    $metaData = is_array( $metaTypeData ) ? array_pop( $metaTypeData ) : $metaTypeData;

    if ( isset( $metaData[ 'instances' ] ) ) {
      $response[ 'instances' ] = $metaData[ 'instances' ];
    }

    if ( isset( $metaData[ 'instanceCount' ] ) ) {
      $response[ 'instanceCount' ] = $metaData[ 'instanceCount' ];
    }

    if ( isset( $metaData[ 'paginatorInfo' ] ) ) {
      $response[ 'paginatorInfo' ] = $metaData[ 'paginatorInfo' ];
    }

    return $response;
  }
}