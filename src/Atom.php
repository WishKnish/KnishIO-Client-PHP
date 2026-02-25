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
use WishKnish\KnishIO\Client\Exception\MoleculeAtomsMissingException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Traits\Json;
use WishKnish\KnishIO\Client\Versions\Versions;

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
 * @property integer|null $index
 * @property string|null $otsFragment
 * @property string $createdAt
 *
 */
class Atom {

  /**
   * @return string[]
   */
  public static function getHashableProps(): array {
    return [
      'position',
      'walletAddress',
      'isotope',
      'token',
      'value',
      'batchId',
      'metaType',
      'metaId',
      'meta',
      'createdAt',
    ];
  }

  /**
   * @param string|null $position
   * @param string|null $walletAddress
   * @param string $isotope
   * @param string|null $token
   * @param string|null $value
   * @param string|null $batchId
   * @param string|null $metaType
   * @param string|null $metaId
   * @param array $meta
   * @param string|null $otsFragment
   * @param int|null $index
   * @param string|null $createdAt
   *
   * @throws JsonException
   */
  public function __construct (
    public ?string $position,
    public ?string $walletAddress,
    public string $isotope,
    public ?string $token = null,
    public ?string $value = null,
    public ?string $batchId = null,
    public ?string $metaType = null,
    public ?string $metaId = null,
    public array $meta = [],
    public ?string $otsFragment = null,
    public ?int $index = null,
    public ?string $createdAt = null,
    public ?string $version = null
  ) {

    // Normalize meta
    if ( $this->meta ) {
      $this->meta = Meta::normalize( $this->meta );
    }

    // Set created at with deterministic timestamp support
    if ( !$this->createdAt ) {
      // Support deterministic testing with KNISHIO_FIXED_TIMESTAMP environment variable
      $fixedTimestamp = getenv('KNISHIO_FIXED_TIMESTAMP');
      if ($fixedTimestamp !== false) {
        // Convert from seconds to milliseconds for deterministic testing
        $this->createdAt = strval(intval($fixedTimestamp) * 1000);
      } else {
        $this->createdAt = Strings::currentTimeMillis();
      }
    }
  }

  /**
   * @param string $isotope
   * @param Wallet|null $wallet
   * @param string|null $value
   * @param string|null $metaType
   * @param string|null $metaId
   * @param AtomMeta|null $meta
   * @param string|null $batchId
   *
   * @return static
   * @throws JsonException
   */
  public static function create(
    string $isotope,
    Wallet $wallet = null,
    string $value = null,
    string $metaType = null,
    string $metaId = null,
    AtomMeta $meta = null,
    string $batchId = null,
  ): self {

    // If meta is not passed - create it
    if ( !$meta ) {
      $meta = new AtomMeta();
    }

    // If wallet has been passed => add related metas
    if ( $wallet ) {
      $meta->setAtomWallet( $wallet );
    }

    // Create the final atom's object
    return new Atom(
      $wallet?->position,
      $wallet?->address,
      $isotope,
      $wallet?->token,
      $value,
      $batchId ?? $wallet?->batchId,
      $metaType,
      $metaId,
      $meta->get(),
    );
  }

  /**
   * Returns hashable values following proven C SDK pattern for cross-platform compatibility
   * Ensures identical molecular hashes across all SDK implementations
   * 
   * @return array
   */
  public function getHashableValues(): array {
    $hashableValues = [];
    
    // Match JavaScript SDK getHashableValues exactly:
    // Include ALL properties from getHashableProps, convert null to empty string (except position/walletAddress)
    
    foreach (static::getHashableProps() as $property) {
      $value = $this->{$property};
      
      // All nullable values are not hashed (only custom keys) - matches JavaScript exactly
      if ($value === null && !in_array($property, ['position', 'walletAddress'])) {
        continue;
      }
      
      // Hashing individual meta keys and values - matches JavaScript exactly
      if ($property === 'meta') {
        foreach ($value as $meta) {
          if (isset($meta['value']) && $meta['value'] !== null) {
            $hashableValues[] = (string) $meta['key'];
            $hashableValues[] = (string) $meta['value'];
          }
        }
      } else {
        // Default value - matches JavaScript exactly (null becomes empty string)
        $hashableValues[] = $value === null ? '' : (string) $value;
      }
    }
    
    return $hashableValues;
  }

  /**
   * @param array $atoms
   * @param string $output
   *
   * @return array|string|null
   * @throws CryptoException
   */
  public static function hashAtoms ( array $atoms, string $output = 'base17' ): array|string|null {
    $atomList = static::sortAtoms( $atoms );
    $molecularSponge = Crypto\Shake256::init();
    $versions = new Versions();

    if (empty($atomList)) {
      throw new MoleculeAtomsMissingException();
    }

    array_walk($atomList, function ($atom) {
      if (!($atom instanceof self)) {
        throw new MoleculeAtomsMissingException();
      }
    });

    //We check that all atoms have an implemented version of reflection
    $availability = array_unique(array_map(static fn(self $atom) => isset($atom->version, $versions->{$atom->version}), $atomList));

    if ( !in_array( false, $availability, true ) ) {
      try {
        $reflection = array_map(static fn (self $atom) => $versions->{$atom->version}::create($atom)->view(), $atomList);
        $molecularSponge->absorb( json_encode( $reflection, JSON_THROW_ON_ERROR ) );
      }
      catch ( Exception $e ) {
        throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
      }
    }
    // Use proven molecular hashing method following exact successful C/Python SDK pattern
    else {
      $numberOfAtoms = (string) count( $atomList );

      // Process each atom following the exact successful SDK pattern (direct property processing)
      foreach ( $atomList as $atom ) {
        // Add atom count for each atom (matching successful SDK pattern)
        try {
          $molecularSponge->absorb( $numberOfAtoms );
        }
        catch ( Exception $e ) {
          throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
        }

        // Add properties using JavaScript SDK pattern exactly:
        // ALL properties from getHashableProps, convert null to empty string (except position/walletAddress)
        
        try {
          // Use JavaScript SDK getHashableValues pattern exactly
          $hashableValues = $atom->getHashableValues();
          
          // Add each hashable value to molecular sponge (matching JavaScript SDK exactly)
          foreach ($hashableValues as $hashableValue) {
            $molecularSponge->absorb( (string) $hashableValue );
          }
        }
        catch ( Exception $e ) {
          throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
        }
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
          $hexHash = bin2hex( $molecularSponge->squeeze( 32 ) );
          $base17Result = Strings::charsetBaseConvert( $hexHash, 16, 17, '0123456789abcdef', '0123456789abcdefg' );
          
          // Ensure base17 result is exactly 64 characters with proper padding (matching successful SDKs)
          if (is_string($base17Result)) {
            $target = str_pad( $base17Result, 64, '0', STR_PAD_LEFT );
          } else {
            $target = null;
          }
          break;
        }
        default:
        {
          $target = null;
        }
      }
    }
    catch ( Exception $e ) {
      throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
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
    return Meta::aggregate( $this->meta );
  }

  /**
   * @return AtomMeta
   */
  public function getAtomMeta(): AtomMeta {
    return new AtomMeta( $this->aggregatedMeta() );
  }

  /**
   * @return int
   */
  public function getValue (): int {
    return $this->value * 1;
  }


}
