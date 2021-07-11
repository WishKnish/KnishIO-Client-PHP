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

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\MoleculeStructure;


/**
 * Class ResponseMoleculeList
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseMoleculeList extends Response
{
	protected $dataKey = 'data.Molecule';


	/**
	 * @param array $data
	 * @return Wallet
	 * @throws \Exception
	 */
	public static function toClientMolecule ( array $data ): MoleculeStructure
	{
		return MoleculeStructure::toObject( $data );
	}


	/**
	 * @return array|null
	 * @throws \Exception
	 */
	public function payload()
	{
		// Get data
		$list = $this->data();
		if (!$list) {
			return null;
		}

		// Get a list of client molecules
		$molecules = [];
		foreach ( $list as $item ) {
			$molecules[] = static::toClientMolecule( $item );
		}

		// Return a molecules list
		return $molecules;
	}

}
