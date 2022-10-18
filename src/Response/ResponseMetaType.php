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
 * Class ResponseMetaType
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseMetaType extends Response {

  /**
   * @var string
   */
  protected string $dataKey = 'data.MetaType';

  /**
   * @return array|array[]
   */
  public function payload (): array {
    $data = $this->data();
    if ( !$data ) {
      return [];
    }

    $result = [
      'instances' => [], 'instanceCount' => [], 'paginatorInfo' => [],
    ];

    $metaData = $data[ 0 ];

    // Duplicate logic from js (@todo $result = $data[ 0 ]?)
    foreach ( $result as $key => $value ) {
      if ( $responseValue = array_get( $metaData, $key ) ) {
        $result[ $key ] = $responseValue;
      }
    }

    return $result;
  }

}
