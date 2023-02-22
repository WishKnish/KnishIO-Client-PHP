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
 * @property string|null $value
 * @property string|null $batchId
 * @property string|null $metaType
 * @property string|null $metaId
 * @property array $metas
 * @property integer|null $index
 * @property string|null $otsFragment
 * @property string $createdAt
 *
 */
class Atom {

    use Json;

    /**
     * @return string[]
     */
    public static function getHashableProps (): array {
        return [
            'index',
            'walletPosition',
            'walletAddress',
            'isotope',
            'tokenSlug',
            'value',
            'batchId',
            'metaType',
            'metaId',
            'metas',
            'createdAt',
        ];
    }

    /**
     * @param string|null $walletPosition
     * @param string|null $walletAddress
     * @param string $isotope
     * @param string|null $tokenSlug
     * @param string|null $value
     * @param string|null $batchId
     * @param string|null $metaType
     * @param string|null $metaId
     * @param array $metas
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
        public ?string $value = null,
        public ?string $batchId = null,
        public ?string $metaType = null,
        public ?string $metaId = null,
        public array $metas = [],
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
     * @param string $isotope
     * @param Wallet|null $wallet
     * @param string|null $value
     * @param string|null $metaType
     * @param string|null $metaId
     * @param AtomMeta|null $metas
     * @param string|null $batchId
     *
     * @return static
     * @throws JsonException
     */
    public static function create (
        string $isotope,
        Wallet $wallet = null,
        string $value = null,
        string $metaType = null,
        string $metaId = null,
        AtomMeta $metas = null,
        string $batchId = null,
    ): self {

        // If meta is not passed - create it
        if ( !$metas ) {
            $metas = new AtomMeta();
        }

        // If wallet has been passed => add related metas
        if ( $wallet ) {
            $metas->addWallet( $wallet );
        }

        // Create the final atom's object
        return new Atom(
            $wallet?->walletPosition,
            $wallet?->walletAddress,
            $isotope,
            $wallet?->tokenSlug,
            $value,
            $batchId ?? $wallet?->batchId,
            $metaType,
            $metaId,
            $metas->get(),
        );
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
