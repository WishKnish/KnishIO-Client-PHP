<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use ArrayObject;
use desktopd\SHA3\Sponge as SHA3;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Traits\Json;

/**
 * Class Atom
 * @package WishKnish\KnishIO\Client
 *
 * @property string $position
 * @property string $walletAddress
 * @property string $isotope
 * @property string|null $token
 * @property string|null $value
 * @property string|null $batchId
 * @property string|null $metaType
 * @property string|null $metaId
 * @property array $meta
 * @property string|null $pubkey
 * @property string|null $characters
 * @property integer|null $index
 * @property string|null $otsFragment
 * @property integer $createdAt
 *
 */
class Atom
{
	use Json;

	public $position;
	public $walletAddress;
	public $isotope;
	public $token;
	public $value;
	public $batchId;
	public $metaType;
	public $metaId;
	public $meta = [];
    public $pubkey;
    public $characters;
	public $index;
	public $otsFragment;
	public $createdAt;

	/**
	 * Atom constructor.
	 *
	 * @param string $position
	 * @param string $walletAddress
	 * @param string $isotope
	 * @param null|string $token
	 * @param null|string $value
     * @param string|null $batchId
	 * @param null|string $metaType
	 * @param null|string $metaId
	 * @param array $meta
     * @param string|null $pubkey
     * @param string|null $characters
	 * @param null|string $otsFragment
     * @param null|integer $index
	 */
	public function __construct (
	    $position,
        $walletAddress,
        $isotope,
        $token = null,
        $value = null,
        $batchId = null,
        $metaType = null,
        $metaId = null,
        array $meta = null,
        $pubkey = null,
        $characters = null,
        $otsFragment = null,
        $index = null
    )
	{
		$this->position = $position;
		$this->walletAddress = $walletAddress;
		$this->isotope = $isotope;
		$this->token = $token;
		$this->value = null !== $value ? ( string ) $value : null;
		$this->batchId = $batchId;

		$this->metaType = $metaType;
		$this->metaId = $metaId;
		$this->meta = $meta ? Meta::normalizeMeta( $meta ) : [];
        $this->pubkey = $pubkey;
        $this->characters = $characters;

		$this->index = $index;
		$this->otsFragment = $otsFragment;
		$this->createdAt = Strings::currentTimeMillis();
	}

	/**
	 * @param array $atoms
	 * @param string $output
	 * @return array[]|string|string[]|null
	 * @throws ReflectionException|Exception
	 */
	public static function hashAtoms ( array $atoms, $output = 'base17' )
	{
		$atomList = static::sortAtoms( $atoms );
		$molecularSponge = Shake256::init();
		$numberOfAtoms = count( $atomList );

		foreach ( $atomList as $atom ) {

			$atom_data = get_object_vars( $atom );

			$molecularSponge->absorb( $numberOfAtoms );

			foreach ( $atom_data as $name => $value ) {

				// Old atoms support (without batch_id field)
				if ( in_array( $name, [ 'batchId', 'pubkey', 'characters', ], true ) && $value === null ) {
					 continue;
				}

				if ( in_array( $name, [ 'otsFragment', 'index', ], true ) ) {
					continue;
				}

				if ( $name === 'meta' ) {

					$list = Meta::normalizeMeta( $value );

					foreach ( $list as $meta ) {

						if ( isset( $meta[ 'value' ] ) ) {

							$molecularSponge->absorb( ( string ) $meta[ 'key' ] );
							$molecularSponge->absorb( ( string ) $meta[ 'value' ] );

						}

					}

					$atom->$name = $list;

					continue;
				}

				if ( in_array( $name, [ 'position', 'walletAddress', 'isotope', ], true ) ) {

					$molecularSponge->absorb( ( string ) $value );
					continue;
				}

				if ( $value !== null ) {

					$molecularSponge->absorb( ( string ) $value );
				}

			}
		}

		switch ( $output ) {
			case 'hex':
			{
				$target = bin2hex( $molecularSponge->squeeze( 32 ) );
				break;
			}
			case 'array':
			{
				$target = str_split( bin2hex( $molecularSponge->squeeze( 32 ) ) );
				break;
			}
			case 'base17':
			{
				$target = str_pad( Strings::charsetBaseConvert( bin2hex( $molecularSponge->squeeze( 32 ) ), 16, 17, '0123456789abcdef', '0123456789abcdefg' ), 64, '0', STR_PAD_LEFT );
				break;
			}
			default:
			{
				$target = null;
			}
		}

		return $target;
	}

    /**
     * @param array $atoms
     * @return array
     */
	public static function sortAtoms ( array $atoms = null )
    {
		$atoms = default_if_null($atoms, []);

        $atomList = ( new ArrayObject( $atoms ) )->getArrayCopy();

        usort($atomList, static function ( self $first, self $second ) {

            if ( $first->index === $second->index ) {
                return 0;
            }

            return $first->index < $second->index ? -1 : 1;

        });

        return $atomList;
    }

}
