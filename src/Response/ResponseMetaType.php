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

use WishKnish\KnishIO\Client\Exception\KnishIOException;

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
     * @throws KnishIOException
     */
    public function payload (): array {
        $data = $this->data();
        if ( !$data ) {
            return [];
        }

        $result = [
            'instances' => [],
            'instanceCount' => [],
            'paginatorInfo' => [],
        ];

        $metas = $data[ 0 ];

        // Duplicate logic from js (@todo $result = $data[ 0 ]?)
        foreach ( $result as $key => $value ) {
            if ( $responseValue = array_get( $metas, $key ) ) {
                $result[ $key ] = $responseValue;
            }
        }

        return $result;
    }

}
