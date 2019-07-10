<?php
namespace WishKnish\KnishIO\Client;

use desktopd\SHA3\Sponge as SHA3;
use BI\BigInteger;
use WishKnish\KnishIO\Client\libraries\Str;

/**
 * Class Wallet
 * @package WishKnish\KnishIO\Client
 *
 * @property $position
 * @property $token
 * @property $key
 * @property $address
 * @property $balance
 * @property $molecules
 * @property $bundle
 *
 */
class Wallet
{
    /**
     * Wallet constructor.
     *
     * @param string $secret
     * @param string $token
     * @param string|null $position
     * @param integer $saltLength
     * @throws \Exception
     */
    public function __construct ( $secret, $token = 'USER', $position = null, $saltLength = 64 )
    {
        $this->position = $position ?: Str::randomString( $saltLength );
        $this->token = $token;
        $this->key = static::generateWalletKey( $secret, $token, $this->position );
        $this->address = static::generateWalletAddress( $this->key );
        $this->balance = 0;
        $this->molecules = [];
        $this->bundle = static::generateBundleHash( $secret );
    }

    /**
     * @param string $key
     * @return string
     * @throws \Exception
     */
    protected static function generateWalletAddress ( $key )
    {
        $digestSponge = SHA3::init( SHA3::SHAKE256 );

        foreach ( Str::chunkSubstr( $key, 128 ) as $idx => $fragment ) {
            $workingFragment = $fragment;

            foreach ( range(1, 16) as $i ) {

                $workingFragment =  bin2hex( SHA3::init( SHA3::SHAKE256 )->absorb( $workingFragment )->squeeze(64) );
            }

            $digestSponge->absorb( $workingFragment );
        }

        return bin2hex( SHA3::init( SHA3::SHAKE256 )->absorb( bin2hex( $digestSponge->squeeze(1024) ) )->squeeze( 32 ) );
    }

    /**
     * @param string $secret
     * @return string
     * @throws \Exception
     */
    protected static function generateBundleHash ( $secret )
    {
        return bin2hex( SHA3::init( SHA3::SHAKE256 )->absorb( $secret )->squeeze( 32 ) );
    }

    /**
     * @param string $secret
     * @param string $token
     * @param string $position
     * @return string
     * @throws \Exception
     */
    public static function generateWalletKey ( $secret, $token, $position )
    {
        // Converting secret to bigInt
        $bigIntSecret = new BigInteger( $secret, 16 );

        // Adding new position to the user secret to produce the indexed key
        $indexedKey = $bigIntSecret->add( new BigInteger( $position, 16 ) );

        // Hashing the indexed key to produce the intermediate key
        $intermediateKeySponge = SHA3::init( SHA3::SHAKE256 )->absorb( $indexedKey->toString( 16 ) );

        if ( '' !== $token ) {
            $intermediateKeySponge->absorb( $token );
        }

        // Hashing the intermediate key to produce the private key
        return bin2hex( SHA3::init( SHA3::SHAKE256 )->absorb(  bin2hex( $intermediateKeySponge->squeeze(1024) ) )->squeeze(1024) );
    }
}