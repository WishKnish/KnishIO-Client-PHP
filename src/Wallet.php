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

namespace WishKnish\KnishIO\Client;

use BI\BigInteger;
use Exception;
use JsonException;
use SodiumException;
use WishKnish\KnishIO\Client\Exception\CodeException;
use WishKnish\KnishIO\Client\Exception\CryptoException;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Soda;
use WishKnish\KnishIO\Client\Libraries\Strings;

/**
 * Class Wallets
 * @package WishKnish\KnishIO\Client
 *
 * @property string $walletPosition
 * @property string $tokenSlug
 * @property string|null $batchId
 * @property string|null $characters
 * @property string|null $key
 * @property string|null $walletAddress
 * @property int $balance
 * @property string|null $type
 * @property string|null $bundleHash
 * @property string|null $privkey
 * @property string|null $pubkey
 * @property string|null $createdAt
 * @property array $tokenUnits
 * @property array $swapRates
 * @property Soda $soda
 */
class Wallet {

    /**
     * @var int
     */
    public int $balance = 0;

    /**
     * @var string|null
     */
    public ?string $walletAddress = null;

    /**
     * @var string|null
     */
    public ?string $bundleHash = null;

    /**
     * @var string|null
     */
    public ?string $type = null;

    /**
     * @var string|null
     */
    public ?string $key = null;

    /**
     * @var string|null
     */
    public ?string $pubkey = null;
    /**
     * @var string|null
     */
    public ?string $createdAt = null;
    /**
     * @var string|null
     */
    public ?string $tokenName = null;
    /**
     * @var string|null
     */
    public ?string $tokenSupply = null;
    /**
     * @var array
     */
    public array $tokenUnits = [];
    /**
     * @var array
     */
    public array $swapRates = [];
    /**
     * @var array
     */
    public array $molecules = [];
    /**
     * @var Soda|null
     */
    protected ?Soda $soda = null;
    /**
     * @var string|null
     */
    private ?string $privkey = null;

    /**
     * @param string|null $secret
     * @param string $tokenSlug
     * @param string|null $walletPosition
     * @param string|null $batchId
     * @param string|null $characters
     *
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function __construct (
        string $secret = null,
        public string $tokenSlug = 'USER',
        public ?string $walletPosition = null,
        public ?string $batchId = null,
        public ?string $characters = null
    ) {
        if ( $secret ) {

            // Set bundleHash from the secret
            $this->bundleHash = Crypto::generateBundleHash( $secret );

            // Generate a position for non-shadow wallet if not initialized
            $this->walletPosition = $this->walletPosition ?? static::generatePosition();

            // Key & address initialization
            $this->key = static::generateKey( $secret, $this->tokenSlug, $this->walletPosition );
            $this->walletAddress = static::generateAddress( $this->key );

            // Soda object initialization
            $this->soda = new Soda( $this->characters );

            // Private & pubkey initialization
            $this->privkey = $this->soda->generatePrivateKey( $this->key );
            $this->pubkey = $this->soda->generatePublicKey( $this->privkey );

            // Set characters
            $this->characters = $this->characters ?? 'BASE64';
        }
    }

    /**
     * @param string $secretOrBundleHash
     * @param string $tokenSlug
     * @param string|null $batchId
     * @param string|null $characters
     *
     * @return Wallet
     * @throws SodiumException
     * @throws KnishIOException
     */
    public static function create ( string $secretOrBundleHash, string $tokenSlug = 'USER', ?string $batchId = null, ?string $characters = null ): Wallet {
        $secret = Crypto::isBundleHash( $secretOrBundleHash ) ? null : $secretOrBundleHash;
        $bundleHash = $secret ? Crypto::generateBundleHash( $secret ) : $secretOrBundleHash;
        $walletPosition = $secret ? static::generatePosition() : null;

        // Wallets initialization
        $wallet = new Wallet( $secret, $tokenSlug, $walletPosition, $batchId, $characters );
        $wallet->bundleHash = $bundleHash;
        return $wallet;
    }

    /**
     * @param array $unitsData
     *
     * @return array
     * @throws KnishIOException
     */
    public static function getTokenUnits ( array $unitsData ): array {
        $result = [];
        foreach ( $unitsData as $unitData ) {
            $result[] = TokenUnit::createFromDB( $unitData );
        }
        return $result;
    }

    /**
     * @param string $secret
     * @param string $tokenSlug
     * @param string $walletPosition
     *
     * @return string
     * @throws KnishIOException
     */
    public static function generateKey ( string $secret, string $tokenSlug, string $walletPosition ): string {

        // Converting secret to bigInt
        // Adding new position to the user secret to produce the indexed key
        $indexedKey = ( new BigInteger( $secret, 16 ) )->add( new BigInteger( $walletPosition, 16 ) );

        try {
            // Hashing the indexed key to produce the intermediate key
            $intermediateKeySponge = Crypto\Shake256::init()
                ->absorb( $indexedKey->toString( 16 ) );

            if ( $tokenSlug !== '' ) {
                $intermediateKeySponge->absorb( $tokenSlug );
            }

            // Hashing the intermediate key to produce the private key
            return bin2hex( Crypto\Shake256::hash( bin2hex( $intermediateKeySponge->squeeze( 1024 ) ), 1024 ) );
        }
        catch ( Exception $e ) {
            throw new CryptoException( $e->getMessage(), null, $e->getCode(), $e );
        }
    }

    /**
     * @param int $saltLength
     *
     * @return string
     * @throws KnishIOException
     */
    protected static function generatePosition ( int $saltLength = 64 ): string {
        return Strings::randomString( $saltLength );
    }

    /**
     * @param string $key
     *
     * @return string
     * @throws KnishIOException
     */
    protected static function generateAddress ( string $key ): string {

        $digestSponge = Crypto\Shake256::init();

        foreach ( Strings::chunkSubstr( $key, 128 ) as $workingFragment ) {
            foreach ( range( 1, 16 ) as $ignored ) {
                $workingFragment = bin2hex( Crypto\Shake256::hash( $workingFragment, 64 ) );
            }

            try {
                $digestSponge->absorb( $workingFragment );
            }
            catch ( Exception $e ) {
                throw new CryptoException( $e->getMessage(), null, $e->getCode(), $e );
            }
        }

        try {
            return bin2hex( Crypto\Shake256::hash( bin2hex( $digestSponge->squeeze( 1024 ) ), 32 ) );
        }
        catch ( Exception $e ) {
            throw new CryptoException( $e->getMessage(), null, $e->getCode(), $e );
        }
    }

    /**
     * @return bool
     */
    public function isShadow (): bool {
        return !$this->walletPosition && !$this->walletAddress;
    }

    /**
     * @param Wallet $sourceWallet
     * @param bool $isRemainder
     *
     * @throws KnishIOException
     */
    public function initBatchId ( Wallet $sourceWallet, bool $isRemainder = false ): void {
        if ( $sourceWallet->batchId ) {
            $this->batchId = $isRemainder ? $sourceWallet->batchId : Crypto::generateBatchId();
        }
    }

    /**
     * @return array
     */
    public function getTokenUnitsData (): array {
        $result = [];
        foreach ( $this->tokenUnits as $tokenUnit ) {
            $result[] = $tokenUnit->toData();
        }
        return $result;
    }

    /**
     * @param array $sendTokenUnits
     * @param Wallet $remainderWallet
     * @param Wallet|null $recipientWallet
     */
    public function splitUnits ( array $sendTokenUnits, Wallet $remainderWallet, ?Wallet $recipientWallet = null ): void {

        // No units supplied, nothing to split
        if ( count( $sendTokenUnits ) === 0 ) {
            return;
        }

        // Init recipient & remainder token units
        $recipientTokenUnits = [];
        $remainderTokenUnits = [];
        foreach ( $this->tokenUnits as $tokenUnit ) {
            if ( in_array( $tokenUnit->id, $sendTokenUnits, true ) ) {
                $recipientTokenUnits[] = $tokenUnit;
            }
            else {
                $remainderTokenUnits[] = $tokenUnit;
            }
        }

        // Reset token units to the sending value
        $this->tokenUnits = $recipientTokenUnits;

        // Set token units to recipient & remainder
        if ( $recipientWallet !== null ) {
            $recipientWallet->tokenUnits = $recipientTokenUnits;
        }

        $remainderWallet->tokenUnits = $remainderTokenUnits;
    }

    /**
     * @param string $message
     * @param ...$pubkeys
     *
     * @return array
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function encryptBinary ( string $message, ...$pubkeys ): array {
        return $this->encryptMessage( base64_encode( $message ), ...$pubkeys );
    }

    /**
     * @param array|string $message
     *
     * @return mixed
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function decryptBinary ( array|string $message ): mixed {
        $decrypt = $this->decryptMessage( $message );

        if ( $decrypt !== null ) {
            $decrypt = base64_decode( $decrypt, true );

            if ( $decrypt === false ) {
                $decrypt = null;
            }
        }

        return $decrypt;
    }

    /**
     * @param mixed $message
     * @param mixed ...$pubkeys
     *
     * @return array
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function encryptMessage ( mixed $message, ...$pubkeys ): array {

        if ( !$this->soda ) {
            throw new CryptoException( 'To use encryption, the wallet must be initialized with a secret argument.' );
        }

        $encrypt = [];
        foreach ( $pubkeys as $pubkey ) {
            // $pubkey = $pubkey instanceof self ? $pubkey->pubkey : $pubkey; // Can use a list of wallets
            $encrypt[ $this->soda->shortHash( $pubkey ) ] = $this->soda->encrypt( $message, $pubkey );
        }

        return $encrypt;
    }

    /**
     * Uses the current wallet's private key to decrypt the given message
     *
     * @param array|string $message
     *
     * @return mixed
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function decryptMessage ( array|string $message ): mixed {

        if ( !$this->soda ) {
            throw new CryptoException( 'To use encryption, the wallet must be initialized with a secret argument.' );
        }

        $encrypted = $message;
        if ( is_array( $message ) ) {

            $hash = $this->soda->shortHash( $this->pubkey );

            if ( !array_key_exists( $hash, $message ) ) {
                throw new CodeException( 'Wallets::decryptMessage - hash does not found for the wallet\'s pubkey.' );
            }

            $encrypted = $message[ $hash ];
        }

        return $this->soda->decrypt( $encrypted, $this->privkey, $this->pubkey );
    }

    /**
     * @param int $amount
     *
     * @return bool
     */
    public function hasEnoughBalance ( int $amount ): bool {
        return $this->balance >= $amount;
    }

}
