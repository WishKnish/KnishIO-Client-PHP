<?php
namespace WishKnish\KnishIO\Client\Libraries;

use desktopd\SHA3\Sponge as SHA3;
use Exception;
use ReflectionException;
use WishKnish\KnishIO\Client\Libraries\Base58Static as B58;

/**
 * Class Soda
 * @package WishKnish\KnishIO\Client\Libraries
 *
 * @property string|null $characters
 *
 */
class Soda
{
    /**
     * @var string|null
     */
    public $characters;

    /**
     * Soda constructor.
     * @param string|null $characters
     * @throws ReflectionException
     */
    public function __construct ( $characters = null )
    {

        $constant = Base58::class . '::' . $characters;

        $this->characters = defined( $constant ) ? constant( $constant ) : Base58::GMP;

        if ( ! extension_loaded( 'sodium' ) ) {

            Sodium::libsodium2sodium();

        }

    }

    /**
     * Encrypts the given message or data with the recipient's public key
     *
     * @param array|object $message
     * @param string $key
     * @return string
     * @throws Exception|ReflectionException
     */
    public function encrypt ( $message, $key )
    {

        return $this->encode(
            sodium_crypto_box_seal(
                json_encode( (array) $message ),
                $this->decode( $key )
            )
        );

    }

    /**
     * Uses the given private key to decrypt an encrypted message
     *
     * @param string $decrypted
     * @param string $privateKey
     * @param string $publicKey
     * @return array|null
     */
    public function decrypt ( $decrypted, $privateKey, $publicKey )
    {

        return json_decode(
            sodium_crypto_box_seal_open(
                $this->decode( $decrypted ),
                sodium_crypto_box_keypair_from_secretkey_and_publickey(
                    $this->decode( $privateKey ),
                    $this->decode( $publicKey )
                )
            ),
            true
        );

    }

    /**
     * Derives a private key for encrypting data with the given key
     *
     * @param string $key
     * @return string
     * @throws Exception|ReflectionException
     */
    public function generatePrivateKey ( $key )
    {

        return $this->encode(
            sodium_crypto_box_secretkey(
                SHA3::init( SHA3::SHAKE256 )
                    ->absorb( $key )
                    ->squeeze( SODIUM_CRYPTO_BOX_KEYPAIRBYTES )
            )
        );

    }

    /**
     * Derives a public key for encrypting data for this wallet's consumption
     *
     * @param string $key
     * @return string
     */
    public function generatePublicKey ( $key )
    {

        return $this->encode( sodium_crypto_box_publickey_from_secretkey( $this->decode( $key ) ) );

    }

    /**
     * @param string $key
     * @return string
     * @throws Exception
     */
    public function shortHash ( $key )
    {
        return $this->encode(
            SHA3::init( SHA3::SHAKE256 )
                ->absorb( $key )
                ->squeeze( 8 )
        );
    }

    /**
     * @param string $data
     * @return string
     */
    private function decode ( $data )
    {

        B58::$options[ 'characters' ] = $this->characters;

        return B58::decode( $data );

    }

    /**
     * @param string $data
     * @return string
     */
    private function encode ( $data )
    {

        B58::$options[ 'characters' ] = $this->characters;

        return B58::encode( $data );

    }

}
