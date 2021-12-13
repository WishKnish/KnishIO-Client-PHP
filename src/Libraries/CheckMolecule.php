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

namespace WishKnish\KnishIO\Client\Libraries;

use Exception;
use Illuminate\Support\Facades\Log;
use ReflectionException;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\Exception\AtomIndexException;
use WishKnish\KnishIO\Client\Exception\AtomsMissingException;
use WishKnish\KnishIO\Client\Exception\BatchIdException;
use WishKnish\KnishIO\Client\Exception\MetaMissingException;
use WishKnish\KnishIO\Client\Exception\MolecularHashMismatchException;
use WishKnish\KnishIO\Client\Exception\MolecularHashMissingException;
use WishKnish\KnishIO\Client\Exception\SignatureMalformedException;
use WishKnish\KnishIO\Client\Exception\SignatureMismatchException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\TransferMalformedException;
use WishKnish\KnishIO\Client\Exception\TransferMismatchedException;
use WishKnish\KnishIO\Client\Exception\TransferRemainderException;
use WishKnish\KnishIO\Client\Exception\TransferToSelfException;
use WishKnish\KnishIO\Client\Exception\TransferUnbalancedException;
use WishKnish\KnishIO\Client\Exception\TransferWalletException;
use WishKnish\KnishIO\Client\Exception\WrongTokenTypeException;
use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;
use WishKnish\KnishIO\Client\Meta;
use WishKnish\KnishIO\Client\MoleculeStructure;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class CheckMolecule
 *
 * @package WishKnish\KnishIO\Client\Libraries
 */
class CheckMolecule {

  /**
   * @param MoleculeStructure $molecule
   * @param Wallet|null $fromWallet
   *
   * @return bool
   */
  public static function verify ( MoleculeStructure $molecule, Wallet $fromWallet = null ): bool {
    $verification_methods = [ 'molecularHash', 'ots', 'isotopeM', 'isotopeP', 'isotopeR', 'isotopeC', 'isotopeV', 'isotopeT', 'isotopeI', 'isotopeU', 'index', 'batchId', ];

    foreach ( $verification_methods as $method ) {

      switch ( $method ) {
        case 'isotopeV':
        {
          static::{$method}( $molecule, $fromWallet );
          break;
        }
        default:
        {
          static::{$method}( $molecule );
        }
      }
    }

    return true;
  }

  /**
   * @param MoleculeStructure $molecule
   *
   * @return bool
   * @throws Exception
   */
  public static function batchId ( MoleculeStructure $molecule ): bool {

    if ( count( $molecule->atoms ) > 0 ) {

      /** @var Atom $subscription */
      $subscription = $molecule->atoms[ 0 ];

      if ( $subscription->isotope === 'V' && $subscription->batchId !== null ) {

        /** @var Atom[] $atoms */
        $atoms = static::isotopeFilter( 'V', $molecule->atoms );
        $remainder = $atoms[ count( $atoms ) - 1 ];

        if ( $subscription->batchId !== $remainder->batchId ) {
          throw new BatchIdException( 'Source batch ID is not equal to the remainder one.' );
        }

        array_walk( $atoms, static function ( Atom $atom ) {
          if ( $atom->batchId === null ) {
            throw new BatchIdException( 'Batch ID can not be null.' );
          }
        } );
      }

      return true;
    }

    throw new BatchIdException();
  }

  /**
   * @param MoleculeStructure $molecule
   *
   * @return bool
   * @throws \JsonException
   */
  public static function isotopeR ( MoleculeStructure $molecule ): bool {
    static::missing( $molecule );

    /** @var Atom $atom */
    foreach ( static::isotopeFilter( 'R', $molecule->atoms ) as $atom ) {

      $metas = Meta::aggregateMeta( $atom->meta );

      foreach ( [ 'callback', 'conditions', 'rule', ] as $key ) {
        if ( !array_key_exists( $key, $metas ) ) {
          throw new MetaMissingException( 'Missing \'' . $key . '\' field in meta.' );
        }
      }

      $conditions = json_decode( $metas[ 'conditions' ], true, 512, JSON_THROW_ON_ERROR );

      if ( $conditions === null ) {
        throw new MetaMissingException( 'Invalid format for conditions.' );
      }

      if ( is_array( $conditions ) ) {
        foreach ( $conditions as $condition ) {
          $keys = array_keys( $condition );

          if ( count( array_intersect( $keys, [ 'key', 'value', 'comparison', ] ) ) < 3 && count( array_intersect( $keys, [ 'managedBy', ] ) ) < 1 ) {
            throw new MetaMissingException( 'Missing field in conditions.' );
          }
        }
      }

      if ( !in_array( strtolower( $metas[ 'callback' ] ), [ 'reject', 'unseat', ], true ) ) {
        $callbacks = json_decode( $metas[ 'callback' ], true, 512, JSON_THROW_ON_ERROR );

        if ( $callbacks === null ) {
          throw new MetaMissingException( 'Invalid format for callback.' );
        }

        if ( $conditions !== 'policy' ) {
          foreach ( $callbacks as $callback ) {
            foreach ( [ 'action', ] as $key ) {
              if ( !array_key_exists( $key, $callback ) ) {
                throw new MetaMissingException( 'Missing \'' . $key . '\' field in callback.' );
              }
            }
          }
        }


        $collect = collect( $callbacks );

        if ( !$collect->contains( fn ( $value, $key ) => in_array( $key, [ 'read', 'write' ] ) ) ) {
          throw new MetaMissingException( 'Mixing rules with politics!' );
        }
      }
    }

    return true;
  }

  /**
   * @param MoleculeStructure $molecule
   *
   * @return bool
   */
  public static function continuId ( MoleculeStructure $molecule ): bool {
    static::missing( $molecule );

    /** @var Atom $atom */
    $atom = reset( $molecule->atoms );

    if ( $atom->token === 'USER' && count( static::isotopeFilter( 'I', $molecule->atoms ) ) < 1 ) {
      throw new AtomsMissingException( 'Missing atom ContinuID' );
    }

    return true;
  }

  /**
   * @param MoleculeStructure $molecule
   *
   * @return bool
   */
  public static function index ( MoleculeStructure $molecule ): bool {

    static::missing( $molecule );

    foreach ( $molecule->atoms as $atom ) {

      if ( null === $atom->index ) {
        throw new AtomIndexException();
      }
    }

    return true;
  }

  /**
   * @param MoleculeStructure $molecule
   *
   * @return bool
   */
  public static function isotopeT ( MoleculeStructure $molecule ): bool {

    static::missing( $molecule );

    // Select all atoms T

    /** @var Atom $atom */
    foreach ( static::isotopeFilter( 'T', $molecule->atoms ) as $atom ) {

      $meta = Meta::aggregateMeta( $atom->meta );
      $metaType = strtolower( ( string ) $atom->metaType );

      if ( $metaType === 'wallet' ) {

        foreach ( [ 'position', 'bundle' ] as $key ) {

          if ( !array_key_exists( $key, $meta ) || empty( $meta[ $key ] ) ) {
            throw new MetaMissingException( 'No or not defined "' . $key . '" in meta' );
          }
        }
      }

      foreach ( [ 'token', ] as $key ) {

        if ( !array_key_exists( $key, $meta ) || empty( $meta[ $key ] ) ) {
          throw new MetaMissingException( 'No or not defined "' . $key . '" in meta' );
        }
      }

      if ( $atom->token !== 'USER' ) {
        throw new WrongTokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
      }

      if ( $atom->index !== 0 ) {
        throw new AtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
      }
    }

    return true;
  }

  /**
   * @param MoleculeStructure $molecule
   *
   * @return bool
   */
  public static function isotopeP ( MoleculeStructure $molecule ): bool {
    return static::isotopeC( $molecule );
  }

  /**
   * @param MoleculeStructure $molecule
   *
   * @return bool
   */
  public static function isotopeC ( MoleculeStructure $molecule ): bool {

    static::missing( $molecule );

    // Select all atoms C

    /** @var Atom $atom */
    foreach ( static::isotopeFilter( 'C', $molecule->atoms ) as $atom ) {

      if ( $atom->token !== 'USER' ) {
        throw new WrongTokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
      }

      if ( $atom->index !== 0 ) {
        throw new AtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
      }
    }

    return true;
  }

  /**
   * @param MoleculeStructure $molecule
   *
   * @return bool
   */
  public static function isotopeI ( MoleculeStructure $molecule ): bool {

    static::missing( $molecule );

    // Select all atoms I

    /** @var Atom $atom */
    foreach ( static::isotopeFilter( 'I', $molecule->atoms ) as $atom ) {

      if ( $atom->token !== 'USER' ) {
        throw new WrongTokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
      }

      if ( $atom->index === 0 ) {
        throw new AtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
      }
    }

    return true;
  }

  /**
   * @param MoleculeStructure $molecule
   *
   * @return bool
   */
  public static function isotopeU ( MoleculeStructure $molecule ): bool {

    static::missing( $molecule );

    // Select all atoms U

    /** @var Atom $atom */
    foreach ( static::isotopeFilter( 'U', $molecule->atoms ) as $atom ) {

      /* if ( $atom->token !== 'AUTH' ) {
          throw new WrongTokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
      } */

      if ( $atom->index !== 0 ) {
        throw new AtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
      }
    }

    return true;
  }

  /**
   * @param MoleculeStructure $molecule
   *
   * @return bool
   */
  public static function isotopeM ( MoleculeStructure $molecule ): bool {

    static::missing( $molecule );

    // Select all atoms M

    /** @var Atom $atom */
    foreach ( static::isotopeFilter( 'M', $molecule->atoms ) as $atom ) {

      if ( empty( $atom->meta ) ) {
        throw new MetaMissingException();
      }

      if ( $atom->token !== 'USER' ) {
        throw new WrongTokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
      }
    }

    return true;
  }

  /**
   * Verification of V-isotope molecules checks to make sure that:
   * 1. we're sending and receiving the same token
   * 2. we're only subtracting on the first atom
   *
   * @param MoleculeStructure $molecule
   * @param Wallet|null $senderWallet
   *
   * @return bool
   */
  public static function isotopeV ( MoleculeStructure $molecule, Wallet $senderWallet = null ): bool {

    static::missing( $molecule );

    $isotopeV = static::isotopeFilter( 'V', $molecule->atoms );

    // Select all atoms V
    if ( empty( $isotopeV ) ) {
      return true;
    }

    // Grabbing the first atom
    /** @var Atom $firstAtom */
    $firstAtom = reset( $molecule->atoms );

    // if there are only two atoms, then this is the burning of tokens
    if ( $firstAtom->isotope === 'V' && count( $isotopeV ) === 2 ) {

      /** @var Atom $endAtom */
      $endAtom = end( $isotopeV );

      if ( $firstAtom->token !== $endAtom->token ) {
        throw new TransferMismatchedException();
      }

      if ( $endAtom->value < 0 ) {
        throw new TransferMalformedException();
      }

      return true;
    }

    // Looping through each V-isotope atom
    $sum = 0.0;
    $value = 0.0;

    // Check sender atom
    if ( Decimal::cmp( $firstAtom->value, 0.0 ) >= 0 ) {
      throw new TransferMalformedException( 'Sender can\'t send negative value.' );
    }

    /** @var Atom $vAtom */
    foreach ( $molecule->atoms as $index => $vAtom ) {

      // Not V? Next...
      if ( $vAtom->isotope !== 'V' ) {
        continue;
      }

      // Making sure we're in integer land
      $value = 1.0 * $vAtom->value;

      // Making sure all V atoms of the same token
      if ( $vAtom->token !== $firstAtom->token ) {
        throw new TransferMismatchedException();
      }

      // Checking non-primary atoms
      if ( $index > 0 ) {

        // Negative V atom in a non-primary position?
        if ( Decimal::cmp( $value, 0.0 ) < 0 ) {
          throw new TransferMalformedException();
        }

        // Cannot be sending and receiving from the same address
        if ( $vAtom->walletAddress === $firstAtom->walletAddress ) {
          throw new TransferToSelfException();
        }
      }

      // Adding this Atom's value to the total sum
      $sum += $value;
    }

    // Does the total sum of all atoms equal the remainder atom's value? (all other atoms must add up to zero)
    if ( !Decimal::equal( $sum, $value ) ) {
      throw new TransferUnbalancedException();
    }

    // If we're provided with a senderWallet argument, we can perform additional checks
    if ( $senderWallet ) {

      $remainder = $senderWallet->balance + $firstAtom->value;

      // Is there enough balance to send?
      if ( Decimal::cmp( $remainder, 0 ) < 0 ) {
        throw new TransferBalanceException();
      }

      // Does the remainder match what should be there in the source wallet, if provided?
      if ( !Decimal::equal( $remainder, $sum ) ) {
        throw new TransferRemainderException();
      }

    } // No senderWallet, but have a remainder?
    else if ( !Decimal::equal( $value, 0.0 ) ) {
      throw new TransferWalletException();
    }

    // Looks like we passed all the tests!
    return true;
  }

  /**
   * Verifies if the hash of all the atoms matches the molecular hash to ensure content has not been messed with
   *
   * @param MoleculeStructure $molecule
   *
   * @return bool
   * @throws ReflectionException|MolecularHashMissingException|AtomsMissingException|MolecularHashMismatchException
   */
  public static function molecularHash ( MoleculeStructure $molecule ): bool {
    static::missing( $molecule );

    if ( $molecule->molecularHash !== Atom::hashAtoms( $molecule->atoms ) ) {
      throw new MolecularHashMismatchException();
    }

    // Looks like we passed all the tests!
    return true;
  }

  /**
   * This section describes the function DecodeOtsFragments(Om, Hm), which is used to transform a collection
   * of signature fragments Om and a molecular hash Hm into a single-use wallet address to be matched against
   * the sender’s address.
   *
   * @param MoleculeStructure $molecule
   *
   * @return bool
   * @throws Exception|MolecularHashMissingException|AtomsMissingException|SignatureMalformedException|SignatureMismatchException
   */
  public static function ots ( MoleculeStructure $molecule ): bool {
    static::missing( $molecule );

    // Determine first atom
    /** @var Atom $firstAtom */
    $firstAtom = reset( $molecule->atoms );

    // Rebuilding OTS out of all the atoms
    $ots = '';

    /** @var Atom $atom */
    foreach ( $molecule->atoms as $atom ) {
      $ots .= $atom->otsFragment;
    }

    // Wrong size? Maybe it's compressed
    if ( mb_strlen( $ots ) !== 2048 ) {

      // Attempt decompression
      $ots = Strings::base64ToHex( $ots );

      // Still wrong? That's a failure
      if ( mb_strlen( $ots ) !== 2048 ) {
        throw new SignatureMalformedException();
      }
    }

    // Key fragments
    $keyFragments = $molecule->signatureFragments( $ots, false );

    // Absorb the hashed Kk into the sponge to receive the digest Dk
    $digest = bin2hex( Shake256::hash( $keyFragments, 1024 ) );

    // Squeeze the sponge to retrieve a 128 byte (64 character) string that should match the sender’s wallet address
    $address = bin2hex( Shake256::hash( $digest, 32 ) );

    // Check the first atom's wallet: is what the molecule must be signed with
    if ( $address !== $firstAtom->walletAddress ) {
      throw new SignatureMismatchException();
    }

    // Looks like we passed all the tests!
    return true;
  }

  /**
   * @param string $isotope
   * @param array $atoms
   *
   * @return array
   */
  public static function isotopeFilter ( string $isotope, array $atoms ): array {
    return array_filter( $atoms, static function ( Atom $atom ) use ( $isotope ) {
      return $isotope === $atom->isotope;
    } );
  }

  /**
   * @param MoleculeStructure $molecule
   */
  private static function missing ( MoleculeStructure $molecule ): void {
    // No molecular hash?
    if ( $molecule->molecularHash === null ) {
      throw new MolecularHashMissingException();
    }

    // No atoms?
    if ( empty( $molecule->atoms ) ) {
      throw new AtomsMissingException();
    }
  }
}
