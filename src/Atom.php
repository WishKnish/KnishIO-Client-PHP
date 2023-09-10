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

use Exception;
use JsonException;
use WishKnish\KnishIO\Client\Exception\CryptoException;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Traits\Json;

/**
 * Class Atom
 * @package WishKnish\KnishIO\Client
 *
 * @property string $walletPosition
 * @property string $walletAddress
 * @property string $isotope
 * @property string|null $tokenSlug
 * @property string|null $metaType
 * @property string|null $metaId
 * @property array $metas
 * @property string|null $value
 * @property array|null $valueUnits
 * @property array|null $swapRates
 * @property string|null $batchId
 * @property string|null $characters
 * @property string|null $pubkey
 * @property int|null $index
 * @property string|null $otsFragment
 * @property string $createdAt
 *
 */
class Atom {

    use Json;

    /**
     * @param string|null $walletPosition
     * @param string|null $walletAddress
     * @param string $isotope
     * @param string|null $tokenSlug
     * @param string|null $metaType
     * @param string|null $metaId
     * @param array|null $metas
     * @param string|null $value
     * @param array|null $valueUnits
     * @param array|null $swapRates
     * @param string|null $batchId
     * @param string|null $characters
     * @param string|null $pubkey
     * @param string|null $otsFragment
     * @param int|null $index
     * @param string|null $createdAt
     *
     * @throws JsonException
     */
    public function __construct (
        public ?string $walletPosition,
        public ?string $walletAddress,
        public string $isotope,
        public ?string $tokenSlug = null,
        public ?string $metaType = null,
        public ?string $metaId = null,
        public ?array $metas = null,
        public ?string $value = null,
        public ?array $valueUnits = null,
        public ?array $swapRates = null,
        public ?string $batchId = null,
        public ?string $characters = 'BASE64',
        public ?string $pubkey = null,
        public ?string $otsFragment = null,
        public ?int $index = null,
        public ?string $createdAt = null,
    ) {

        // Normalize meta
        if ( $this->metas ) {
            $this->metas = Meta::normalize( $this->metas );
        }

        // Set created at
        if ( !$this->createdAt ) {
            $this->createdAt = Strings::currentTimeMillis();
        }
    }

    /**
     * @return string[]
     */
    public static function getHashableProps (): array {
        return [
            'walletPosition',
            'walletAddress',
            'isotope',
            'tokenSlug',
            'metaType',
            'metaId',
            'metas',
            'value',
            'valueUnits',
            'swapRates',
            'batchId',
            'index',
            'characters',
            'pubkey',
            'createdAt',
        ];
    }

    /**
     * @param string $isotope
     * @param Wallet|null $wallet
     * @param string|null $metaType
     * @param string|null $metaId
     * @param AtomMeta|null $metas
     * @param string|null $value
     * @param array|null $valueUnits
     * @param array|null $swapRates
     * @param string|null $batchId
     * @param string|null $characters
     * @param string|null $pubkey
     *
     * @return static
     * @throws JsonException
     */
    public static function create (
        string $isotope,
        Wallet $wallet = null,
        string $metaType = null,
        string $metaId = null,
        AtomMeta $metas = null,
        string $value = null,
        array $valueUnits = null,
        array $swapRates = null,
        string $batchId = null,
        string $characters = null,
        string $pubkey = null
    ): self {

        // If meta is not passed - create it
        if ( !$metas ) {
            $metas = new AtomMeta();
        }

        // If wallet has been passed => add related metas
        // if ( $wallet ) {
        //    $metas->addWallet( $wallet );
        // }

        // Create the final atom's object
        return new Atom(
            $wallet?->walletPosition,
            $wallet?->walletAddress,
            $isotope,
            $wallet?->tokenSlug,
            $metaType,
            $metaId,
            $metas->get(),
            $value,
            $valueUnits,
            $swapRates,
            $batchId ?? $wallet?->batchId,
            $characters ?? $wallet->characters,
            $pubkey ?? $wallet->pubkey
        );
    }

    /**
     * @param array $atoms
     * @param string $output
     *
     * @return array|string|null
     * @throws KnishIOException
     * @throws JsonException
     */
    public static function hashAtoms ( array $atoms, string $output = 'base17' ): array|string|null {
        $atomList = static::sortAtoms( $atoms );
        $molecularSponge = Crypto\Shake256::init();

        $hashableValues = [];
        foreach ( $atomList as $atom ) {
            $hashableValues[] = json_encode( $atom->getHashableValues(), JSON_THROW_ON_ERROR );
        }

        // Add hash values to the sponge
        foreach ( $hashableValues as $hashableValue ) {
            try {
                $molecularSponge->absorb( $hashableValue );
            }
            catch ( Exception $e ) {
                throw new CryptoException( $e->getMessage(), $hashableValue, $e->getCode(), $e );
            }
        }

        try {
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
        }
        catch ( Exception $e ) {
            throw new CryptoException( $e->getMessage(), $hashableValues, $e->getCode(), $e );
        }

        return $target;
    }

    /**
     * @param array $atoms
     *
     * @return array
     */
    public static function sortAtoms ( array $atoms = [] ): array {
        usort( $atoms, static function ( $atom1, $atom2 ) {
            return $atom1->index < $atom2->index ? -1 : 1;
        } );
        return $atoms;
    }

    /**
     * @return array
     * @throws JsonException
     */
    public function getHashableValues (): array {
        $hashableValues = [];
        foreach ( static::getHashableProps() as $property ) {
            $value = $this->$property;

            // All null values won't get hashed
            if ( $value === null ) {
                continue;
            }

            // Special code for meta property
            if ( $property === 'metas' ) {
                $hashableValues[] = json_encode( $value, JSON_THROW_ON_ERROR );
            }
            // Default value
            else {
                $hashableValues[] = ( string ) $value;
            }
        }

        return $hashableValues;
    }

    /**
     * @return array
     */
    public function aggregatedMeta (): array {
        return Meta::aggregate( $this->metas );
    }

    /**
     * @param string $property
     * @param $value
     *
     * @throws JsonException
     * @todo change to __set?
     */
    public function setProperty ( string $property, $value ): void {
        $property = array_get( [
            'tokenSlug' => 'tokenSlug',
            'metas' => 'meta',
        ], $property, $property );

        // Meta json specific logic (if meta does not initialized)
        if ( !$this->metas && $property === 'metasJson' ) {
            $metas = json_decode( $value, true );
            if ( $metas !== null ) {
                $this->metas = Meta::normalize( $metas );
            }
        } // Default meta set
        else {
            $this->$property = $value;
        }
    }

    /**
     * @return int
     */
    public function getValue (): int {
        return $this->value * 1;
    }

}
