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

use JsonException;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Exception\MetaMissingException;
use WishKnish\KnishIO\Client\Exception\MoleculeAtomIndexException;
use WishKnish\KnishIO\Client\Exception\MoleculeAtomsMissingException;
use WishKnish\KnishIO\Client\Exception\MoleculeHashMismatchException;
use WishKnish\KnishIO\Client\Exception\MoleculeHashMissingException;
use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMalformedException;
use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMismatchException;
use WishKnish\KnishIO\Client\Exception\TokenTypeException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\TransferMalformedException;
use WishKnish\KnishIO\Client\Exception\TransferMismatchedException;
use WishKnish\KnishIO\Client\Exception\TransferRemainderException;
use WishKnish\KnishIO\Client\Exception\TransferToSelfException;
use WishKnish\KnishIO\Client\Exception\TransferUnbalancedException;
use WishKnish\KnishIO\Client\Exception\TransferWalletException;
use WishKnish\KnishIO\Client\Exception\WalletBatchException;
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
     * @param MoleculeStructure $molecule
     *
     * @throws KnishIOException
     */
    public function __construct ( private readonly MoleculeStructure $molecule ) {

        // No molecular hash?
        if ( $molecule->molecularHash === null ) {
            throw new MoleculeHashMissingException();
        }

        // No atoms?
        if ( empty( $molecule->atoms ) ) {
            throw new MoleculeAtomsMissingException();
        }

        // Check atom indexes
        foreach ( $this->molecule->atoms as $atom ) {
            if ( null === $atom->index ) {
                throw new MoleculeAtomIndexException();
            }
        }
    }

    /**
     * @param Wallet|null $fromWallet
     *
     * @return void
     * @throws KnishIOException
     * @throws JsonException
     */
    public function verify ( Wallet $fromWallet = null ): void {
        $this->molecularHash();
        $this->ots();
        $this->isotopeM();
        $this->isotopeR();
        $this->isotopeC();
        $this->isotopeVB( $fromWallet );
        $this->isotopeT();
        $this->isotopeI();
        $this->isotopeU();
        $this->batchId();
    }

    /**
     * Check batch ID
     * @throws KnishIOException
     */
    public function batchId (): void {

        /** @var Atom $sourceAtom */
        $sourceAtom = $this->molecule->atoms[ 0 ];
        if ( $sourceAtom->isotope === 'V' && $sourceAtom->batchId !== null ) {

            /** @var Atom[] $atoms */
            $atoms = $this->molecule->getIsotopes( 'V' );
            $remainderAtom = $atoms[ count( $atoms ) - 1 ];

            if ( $sourceAtom->batchId !== $remainderAtom->batchId ) {
                throw new WalletBatchException( 'Source batch ID is not equal to the remainder one.' );
            }

            array_walk( $atoms, static function ( Atom $atom ) {
                if ( $atom->batchId === null ) {
                    throw new WalletBatchException( 'Batch ID can not be null.' );
                }
            } );
        }
    }

    /**
     * @throws JsonException
     * @throws KnishIOException
     */
    public function isotopeR (): void {

        /** @var Atom $atom */
        foreach ( $this->molecule->getIsotopes( 'R' ) as $atom ) {

            $metas = $atom->aggregatedMeta();

            if ( array_key_exists( 'policy', $metas ) ) {
                $policy = json_decode( $metas[ 'policy' ], true, 512, JSON_THROW_ON_ERROR );

                if ( !array_every( array_keys( $policy ), static fn( $value ) => in_array( $value, [
                    'read',
                    'write'
                ], true ) ) ) {
                    throw new MetaMissingException( 'Check::isotopeR() - Mixing rules with politics!' );
                }
            }

            if ( array_key_exists( 'rule', $metas ) ) {

                foreach ( [
                    'callback',
                    'conditions',
                    'rule',
                ] as $key ) {
                    if ( !array_key_exists( $key, $metas ) ) {
                        throw new MetaMissingException( 'Missing \'' . $key . '\' field in metas.' );
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
                                'key',
                                'value',
                                'comparison',
                            ] ) ) < 3 && count( array_intersect( $keys, [ 'managedBy', ] ) ) < 1 ) {
                            throw new MetaMissingException( 'Missing field in conditions.' );
                        }
                    }
                }

                if ( !in_array( strtolower( $metas[ 'callback' ] ), [
                    'reject',
                    'unseat',
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
     * @throws KnishIOException
     */
    public function continuId (): void {

        /** @var Atom $atom */
        $atom = reset( $this->molecule->atoms );

        if ( $atom->tokenSlug === 'USER' && count( $this->molecule->getIsotopes( 'I' ) ) < 1 ) {
            throw new MoleculeAtomsMissingException( 'Missing atom ContinuID' );
        }

    }

    /**
     * Check isotope T
     * @throws KnishIOException
     */
    public function isotopeT (): void {

        /** @var Atom $atom */
        foreach ( $this->molecule->getIsotopes( 'T' ) as $atom ) {

            $metas = $atom->aggregatedMeta();
            $metaType = strtolower( ( string ) $atom->metaType );

            // Check required meta keys closure
            $checkRequiredMetaKeys = static function ( array $keys ) use ( $metas ) {
                foreach ( $keys as $key ) {
                    if ( !array_key_exists( $key, $metas ) || empty( $metas[ $key ] ) ) {
                        throw new MetaMissingException( 'Key "' . $key . '" not defined in metas.' );
                    }
                }
            };

            if ( $metaType === 'wallet' ) {
                $checkRequiredMetaKeys( [
                    'walletPosition',
                    'bundleHash'
                ] );
            }

            $checkRequiredMetaKeys( [ 'token' ] );

            if ( $atom->tokenSlug !== 'USER' ) {
                throw new TokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
            }

            if ( $atom->index !== 0 ) {
                throw new MoleculeAtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
            }
        }
    }

    /**
     * Check isotope C
     * @throws KnishIOException
     */
    public function isotopeC (): void {

        /** @var Atom $atom */
        foreach ( $this->molecule->getIsotopes( 'C' ) as $atom ) {

            if ( $atom->tokenSlug !== 'USER' ) {
                throw new TokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
            }

            if ( $atom->index !== 0 ) {
                throw new MoleculeAtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
            }
        }
    }

    /**
     * Check isotope I
     * @throws KnishIOException
     */
    public function isotopeI (): void {

        /** @var Atom $atom */
        foreach ( $this->molecule->getIsotopes( 'I' ) as $atom ) {

            if ( $atom->tokenSlug !== 'USER' ) {
                throw new TokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
            }

            if ( $atom->index === 0 ) {
                throw new MoleculeAtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
            }
        }
    }

    /**
     * Check isotope U
     * @throws KnishIOException
     */
    public function isotopeU (): void {

        /** @var Atom $atom */
        foreach ( $this->molecule->getIsotopes( 'U' ) as $atom ) {

            if ( $atom->tokenSlug !== 'USER' ) {
                throw new TokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
            }

            if ( $atom->index !== 0 ) {
                throw new MoleculeAtomIndexException( 'Invalid isotope "' . $atom->isotope . '" index' );
            }
        }
    }

    /**
     * Check isotope M
     * @throws KnishIOException
     */
    public function isotopeM (): void {

        /** @var Atom $atom */
        foreach ( $this->molecule->getIsotopes( 'M' ) as $atom ) {

            if ( empty( $atom->metas ) ) {
                throw new MetaMissingException();
            }

            if ( $atom->tokenSlug !== 'USER' ) {
                throw new TokenTypeException( 'Invalid token name for ' . $atom->isotope . ' isotope' );
            }
        }
    }

    /**
     * Verification of V, B isotope molecules checks to make sure that:
     * 1. we're sending and receiving the same token
     * 2. we're only subtracting on the first atom
     *
     * @param Wallet|null $senderWallet
     *
     * @throws KnishIOException
     */
    public function isotopeVB ( Wallet $senderWallet = null ): void {

        // Get atoms with V OR B isotopes
        $atoms = $this->molecule->getIsotopes( [
            'V',
            'B'
        ] );

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

            if ( $firstAtom->tokenSlug !== $endAtom->tokenSlug ) {
                throw new TransferMismatchedException();
            }

            if ( $endAtom->getValue() < 0 ) {
                throw new TransferMalformedException();
            }

            return;
        }

        // Looping through each V-isotope atom
        $sum = 0;
        $value = 0;

        // Check sender atom
        if ( $firstAtom->getValue() >= 0 ) {
            throw new TransferMalformedException( 'Sender can\'t send negative value or zero.' );
        }

        /** @var Atom $vAtom */
        foreach ( $atoms as $index => $vAtom ) {

            // Making sure we're in integer land
            $value = $vAtom->getValue();

            // Making sure all V atoms of the same token
            if ( $vAtom->tokenSlug !== $firstAtom->tokenSlug ) {
                throw new TransferMismatchedException();
            }

            // Checking non-primary atoms
            if ( $index > 0 ) {

                // Negative V atom in a non-primary position?
                if ( $value < 0 ) {
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
        if ( $sum !== $value ) {
            throw new TransferUnbalancedException();
        }

        // If we're provided with a senderWallet argument, we can perform additional checks
        if ( $senderWallet ) {

            $remainder = $senderWallet->balance + $firstAtom->getValue();

            // Is there enough balance to send?
            if ( $remainder < 0 ) {
                throw new TransferBalanceException();
            }

            // Does the remainder match what should be there in the source wallet, if provided?
            if ( $remainder !== $sum ) {
                throw new TransferRemainderException();
            }

        }
        // No senderWallet, but have a remainder?
        else if ( $value !== 0 ) {
            throw new TransferWalletException();
        }
    }

    /**
     * Verifies if the hash of all the atoms matches the molecular hash to ensure content has not been messed with
     * @throws KnishIOException
     * @throws JsonException
     */
    public function molecularHash (): void {
        if ( $this->molecule->molecularHash !== Atom::hashAtoms( $this->molecule->atoms ) ) {
            throw new MoleculeHashMismatchException();
        }
    }

    /**
     * This section describes the function DecodeOtsFragments(Om, Hm), which is used to transform a collection
     * of signature fragments Om and a molecular hash Hm into a single-use wallet address to be matched against
     * the sender’s address.
     *
     * @return void
     * @throws KnishIOException
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
                throw new MoleculeSignatureMalformedException();
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
            $signingAddress = array_get( json_decode( $signingWallet, true ), 'walletAddress' );
        }

        // Check the first atom's wallet: is what the molecule must be signed with
        if ( $address !== $signingAddress ) {
            throw new MoleculeSignatureMismatchException();
        }
    }

}
