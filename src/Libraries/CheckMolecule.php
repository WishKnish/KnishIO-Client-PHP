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
use JsonException;
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
use WishKnish\KnishIO\Client\MoleculeStructure;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class CheckMolecule
 *
 * @package WishKnish\KnishIO\Client\Libraries
 */
class CheckMolecule {

  /**
   * CheckMolecule constructor.
   *
   * @param MoleculeStructure $molecule
   */
  public function __construct ( private MoleculeStructure $molecule ) {
    // No molecular hash?
    if ( $molecule->molecularHash === null ) {
      throw new MolecularHashMissingException();
    }

    // No atoms?
    if ( empty( $molecule->atoms ) ) {
      throw new AtomsMissingException();
    }
  }

  /**
   * @param Wallet|null $fromWallet
   *
   * @throws JsonException
   */
  public function verify ( Wallet $fromWallet = null ): void {
    $this->molecularHash();
    $this->ots();
    $this->isotopeM();
    $this->isotopeP();
    $this->isotopeR();
    $this->isotopeC();
    $this->isotopeVB( $fromWallet );
    $this->isotopeT();
    $this->isotopeI();
    $this->isotopeU();
    $this->index();
    $this->batchId();
  }

  /**
   * Check batch ID
   */
  public function batchId (): void {

    /** @var Atom $sourceAtom */
    $sourceAtom = $this->molecule->atoms[ 0 ];
    if ( $sourceAtom->isotope === 'V' && $sourceAtom->batchId !== null ) {

      /** @var Atom[] $atoms */
      $atoms = $this->molecule->getIsotopes( 'V' );
      $remainderAtom = $atoms[ count( $atoms ) - 1 ];

      if ( $sourceAtom->batchId !== $remainderAtom->batchId ) {
        throw new BatchIdException( 'Source batch ID is not equal to the remainder one.' );
      }

      array_walk( $atoms, static function ( Atom $atom ) {
        if ( $atom->batchId === null ) {
          throw new BatchIdException( 'Batch ID can not be null.' );
        }
      } );
    }
  }

  /**
   * @throws JsonException
   */
  public function isotopeR (): void {

    /** @var Atom $atom */
    foreach ( $this->molecule->getIsotopes( 'R' ) as $atom ) {

      $metas = $atom->aggregatedMeta();

      if ( array_key_exists( 'policy', $metas ) ) {
        $policy = json_decode( $metas[ 'policy' ], true, 512, JSON_THROW_ON_ERROR );

        if ( !array_every( array_keys( $policy ), static fn( $value ) => in_array( $value, [
          'read', 'write'
        ], true ) ) ) {
          throw new MetaMissingException( 'Check::isotopeR() - Mixing rules with politics!' );
        }
      }

      if ( array_key_exists( 'rule', $metas ) ) {

        foreach ( [
          'callback', 'conditions', 'rule',
        ] as $key ) {
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

            if ( count( array_intersect( $keys, [
                'key', 'value', 'comparison',
              ] ) ) < 3 && count( array_intersect( $keys, [ 'managedBy', ] ) ) < 1 ) {
              throw new MetaMissingException( 'Missing field in conditions.' );
            }
          }
        }

        if ( !in_array( strtolower( $metas[ 'callback' ] ), [
          'reject', 'unseat',
        ], true ) ) {
          $callbacks = json_decode( $metas[ 'callback' ], true, 512, JSON_THROW_ON_ERROR );

          if ( $callbacks === null ) {
            throw new MetaMissingException( 'Invalid format for callback.' );
          }
        }
      }
    }
  }

  /**
   * Check ContinuID
   */
  public function continuId (): void {

    /** @var Atom $atom */
    $atom = reset( $this->molecule->atoms );

    if ( $atom->token === 'USER' && count( $this->molecule->getIsotopes( 'I' ) ) < 1 ) {
      throw new AtomsMissingException( 'Missing atom ContinuID' );
    }

  }

  /**
   * Check index
   */
  public function index (): void {
    foreach ( $this->molecule->atoms as $atom ) {
      if ( null === $atom->index ) {
        throw new AtomIndexException();
      }
    }
  }

  /**
   * Check isotope T
   */
  public function isotopeT (): void {

    /** @var Atom $atom */
    foreach ( $this->molecule->getIsotopes( 'T' ) as $atom ) {

      $meta = $atom->aggregatedMeta();
      $metaType = strtolower( ( string ) $atom->metaType );

      // Check required meta keys closure
      $checkRequiredMetaKeys = static function ( array $keys ) use ( $meta ) {
        foreach ( $keys as $key ) {
          if ( !array_key_exists( $key, $meta ) || empty( $meta[ $key ] ) ) {
            throw new MetaMissingException( 'No or not defined "' . $key . '" in meta' );
          }
        }
      };

      if ( $metaType === 'wallet' ) {
        $checkRequiredMetaKeys( [ 'position', 'bundle' ] );
      }

      $checkRequiredMetaKeys( [ 'token' ] );

      if ( $atom->token !== 'USER' ) {
        throw new WrongTokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
      }

      if ( $atom->index !== 0 ) {
        throw new AtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
      }
    }
  }

  /**
   * Check isotope P
   */
  public function isotopeP (): void {
    $this->isotopeC();
  }

  /**
   * Check isotope C
   */
  public function isotopeC (): void {

    /** @var Atom $atom */
    foreach ( $this->molecule->getIsotopes( 'C' ) as $atom ) {

      if ( $atom->token !== 'USER' ) {
        throw new WrongTokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
      }

      if ( $atom->index !== 0 ) {
        throw new AtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
      }
    }
  }

  /**
   * Check isotope I
   */
  public function isotopeI (): void {

    /** @var Atom $atom */
    foreach ( $this->molecule->getIsotopes( 'I' ) as $atom ) {

      if ( $atom->token !== 'USER' ) {
        throw new WrongTokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
      }

      if ( $atom->index === 0 ) {
        throw new AtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
      }
    }
  }

  /**
   * Check isotope U
   */
  public function isotopeU (): void {

    /** @var Atom $atom */
    foreach ( $this->molecule->getIsotopes( 'U' ) as $atom ) {

      /* if ( $atom->token !== 'AUTH' ) {
          throw new WrongTokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
      } */

      if ( $atom->index !== 0 ) {
        throw new AtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
      }
    }
  }

  /**
   * Check isotope M
   */
  public function isotopeM (): void {

    /** @var Atom $atom */
    foreach ( $this->molecule->getIsotopes( 'M' ) as $atom ) {

      if ( empty( $atom->meta ) ) {
        throw new MetaMissingException();
      }

      if ( $atom->token !== 'USER' ) {
        throw new WrongTokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
      }
    }
  }

  /**
   * Verification of V, B isotope molecules checks to make sure that:
   * 1. we're sending and receiving the same token
   * 2. we're only subtracting on the first atom
   *
   * @param Wallet|null $senderWallet
   */
  public function isotopeVB ( Wallet $senderWallet = null ): void {

    // Get atoms with V OR B isotopes
    $atoms = $this->molecule->getIsotopes( [ 'V', 'B' ] );

    // V & B isotopes does not found
    if ( !$atoms ) {
      return;
    }

    // Grabbing the first atom
    /** @var Atom $firstAtom */
    $firstAtom = $atoms[ 0 ];

    // if there are only two atoms, then this is the burning of tokens
    if ( count( $atoms ) === 2 ) {

      /** @var Atom $endAtom */
      $endAtom = end( $atoms );

      if ( $firstAtom->token !== $endAtom->token ) {
        throw new TransferMismatchedException();
      }

      if ( $endAtom->value < 0 ) {
        throw new TransferMalformedException();
      }

      return;
    }

    // Looping through each V-isotope atom
    $sum = 0.0;
    $value = 0.0;

    // Check sender atom
    if ( Decimal::cmp( $firstAtom->value, 0.0 ) >= 0 ) {
      throw new TransferMalformedException( 'Sender can\'t send negative value.' );
    }

    /** @var Atom $vAtom */
    foreach ( $atoms as $index => $vAtom ) {

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
        if ( $vAtom->walletAddress === $firstAtom->walletAddress && // Check wallet address
          !( $firstAtom->isotope === 'B' && $vAtom->isotope === 'B' ) // BVB transaction, do not check wallet address
        ) {
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

    }
    // No senderWallet, but have a remainder?
    else if ( !Decimal::equal( $value, 0.0 ) ) {
      throw new TransferWalletException();
    }
  }

  /**
   * Verifies if the hash of all the atoms matches the molecular hash to ensure content has not been messed with
   *
   * @throws Exception
   */
  public function molecularHash (): void {
    if ( $this->molecule->molecularHash !== Atom::hashAtoms( $this->molecule->atoms ) ) {
      throw new MolecularHashMismatchException();
    }
  }

  /**
   * This section describes the function DecodeOtsFragments(Om, Hm), which is used to transform a collection
   * of signature fragments Om and a molecular hash Hm into a single-use wallet address to be matched against
   * the sender’s address.
   *
   * @throws Exception|MolecularHashMissingException|AtomsMissingException|SignatureMalformedException|SignatureMismatchException
   */
  public function ots (): void {

    // Determine first atom
    /** @var Atom $firstAtom */
    $firstAtom = reset( $this->molecule->atoms );

    // Rebuilding OTS out of all the atoms
    $ots = '';

    /** @var Atom $atom */
    foreach ( $this->molecule->atoms as $atom ) {
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
    $keyFragments = $this->molecule->signatureFragments( $ots, false );

    // Absorb the hashed Kk into the sponge to receive the digest Dk
    $digest = bin2hex( Shake256::hash( $keyFragments, 1024 ) );

    // Squeeze the sponge to retrieve a 128 byte (64 character) string that should match the sender’s wallet address
    $address = bin2hex( Shake256::hash( $digest, 32 ) );

    // Get a signing address
    $signingAddress = $firstAtom->walletAddress;

    // Try to get custom signing position from the metas (local molecule with server secret)
    if ( $signingWallet = array_get( $firstAtom->aggregatedMeta(), 'signingWallet' ) ) {
      $signingAddress = array_get( json_decode( $signingWallet, true ), 'address' );
    }

    // Check the first atom's wallet: is what the molecule must be signed with
    if ( $address !== $signingAddress ) {
      throw new SignatureMismatchException();
    }
  }

}
