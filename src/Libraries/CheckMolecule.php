<?php
namespace WishKnish\KnishIO\Client\Libraries;


use desktopd\SHA3\Sponge as SHA3;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\Exception\AtomIndexException;
use WishKnish\KnishIO\Client\Exception\AtomsMissingException;
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
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class CheckMolecule
 *
 * @package WishKnish\KnishIO\Client\Libraries
 */
class CheckMolecule
{
    /**
     * @param Molecule $molecule
     * @return bool
     * @throws AtomsMissingException|AtomIndexException
     */
    public static function index ( Molecule $molecule )
    {

        static::missing( $molecule );

        foreach ( $molecule->atoms as $atom ) {

            if ( null === $atom->index ) {

                throw new AtomIndexException();

            }

        }

        return true;

    }

    /**
     * @param Molecule $molecule
     * @return bool
     */
    public static function isotopeM ( Molecule $molecule )
    {

        static::missing( $molecule );

        // Select all atoms M
        foreach ( static::isotopeFilter('M', $molecule->atoms ) as  $atom ) {

            if ( empty( $atom->meta ) ) {

                throw new MetaMissingException();

            }

        }

        return true;

    }

    /**
     * Verification of V-isotope molecules checks to make sure that:
     * 1. we're sending and receiving the same token
     * 2. we're only subtracting on the first atom
     *
     * @param Molecule $molecule
     * @param Wallet $senderWallet
     * @return bool
     * @throws AtomsMissingException|TransferMismatchedException|TransferMalformedException|TransferToSelfException|TransferUnbalancedException|TransferBalanceException|TransferRemainderException
     */
    public static function isotopeV ( Molecule $molecule, Wallet $senderWallet = null )
    {

        static::missing( $molecule );

        // Select all atoms V
        if ( empty( static::isotopeFilter('V', $molecule->atoms ) ) ) {

            return true;

        }

        // Grabbing the first atom
        $firstAtom = \reset( $molecule->atoms );

        // Looping through each V-isotope atom
        $sum = 0;
        $value = 0;

        foreach ( $molecule->atoms as $index => $vAtom ) {

            // Not V? Next...
            if ( $vAtom->isotope !== 'V' ) {

                continue;

            }

            // Making sure we're in integer land
            $value = 1 * $vAtom->value;

            // Making sure all V atoms of the same token
            if ( $vAtom->token !== $firstAtom->token ) {

                throw new TransferMismatchedException();

            }

            // Checking non-primary atoms
            if ( $index > 0 ) {

                // Negative V atom in a non-primary position?
                if ( $value < 0 ) {

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
        if ( $sum !== $value ) {

            throw new TransferUnbalancedException();

        }

        // If we're provided with a senderWallet argument, we can perform additional checks
        if ( $senderWallet ) {

            $remainder = $senderWallet->balance + $firstAtom->value;

            // Is there enough balance to send?
            if ( $remainder < 0 ) {

                throw new TransferBalanceException();

            }

            // Does the remainder match what should be there in the source wallet, if provided?
            if ( $remainder !== $sum ) {

                throw new TransferRemainderException();

            }

        } // No senderWallet, but have a remainder?
        else if ( $value !== 0 ) {

            throw new TransferRemainderException();

        }

        // Looks like we passed all the tests!
        return true;

    }

    /**
     * Verifies if the hash of all the atoms matches the molecular hash to ensure content has not been messed with
     *
     * @param Molecule $molecule
     * @return bool
     * @throws \ReflectionException|MolecularHashMissingException|AtomsMissingException|MolecularHashMismatchException
     */
    public static function molecularHash ( Molecule $molecule )
    {

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
     * @param Molecule $molecule
     * @return bool
     * @throws \Exception|MolecularHashMissingException|AtomsMissingException|SignatureMalformedException|SignatureMismatchException
     */
    public static function ots ( Molecule $molecule )
    {

        static::missing( $molecule );

        // Determine first atom
        $firstAtom = \reset( $molecule->atoms );

        // Convert Hm to numeric notation via EnumerateMolecule(Hm)
        $normalizedHash = static::normalizedHash( $molecule->molecularHash );

        // Rebuilding OTS out of all the atoms
        $ots = '';

        foreach ( $molecule->atoms as $atom ) {

            $ots .= $atom->otsFragment;

        }

        // Wrong size? Maybe it's compressed
        if ( \mb_strlen( $ots ) !== 2048 ) {

            // Attempt decompression
            $ots = Strings::base64ToHex( $ots );

            // Still wrong? That's a failure
            if ( \mb_strlen( $ots ) !== 2048 ) {

                throw new SignatureMalformedException();

            }

        }

        // First atom's wallet is what the molecule must be signed with
        $walletAddress = $firstAtom->walletAddress;

        // Subdivide Kk into 16 segments of 256 bytes (128 characters) each
        $otsChunks = Strings::chunkSubstr( $ots, 128 );

        $keyFragments = '';

        foreach ( $otsChunks as $index => $otsChunk ) {

            // Iterate a number of times equal to 8+Hm[i]
            $workingChunk = $otsChunk;

            for ( $iterationCount = 0, $condition = 8 + $normalizedHash[ $index ]; $iterationCount < $condition; $iterationCount++ ) {

                $workingChunk = \bin2hex(
                    SHA3::init( SHA3::SHAKE256 )
                        ->absorb( $workingChunk )
                        ->squeeze( 64 )
                );

            }

            $keyFragments .= $workingChunk;

        }

        // Absorb the hashed Kk into the sponge to receive the digest Dk
        $digest = \bin2hex(
            SHA3::init( SHA3::SHAKE256 )
                ->absorb( $keyFragments )
                ->squeeze( 1024 )
        );

        // Squeeze the sponge to retrieve a 128 byte (64 character) string that should match the sender’s wallet address
        $address = \bin2hex(
            SHA3::init( SHA3::SHAKE256 )
                ->absorb( $digest )
                ->squeeze( 32 )
        );

        if ( $address !== $walletAddress ) {

            throw new SignatureMismatchException();

        }

        // Looks like we passed all the tests!
        return true;

    }

    /**
     * @param string $isotope
     * @param array $atoms
     * @return array
     */
    public static function isotopeFilter ( $isotope, array $atoms )
    {

        return \array_filter(
            $atoms,
            static function ( Atom $atom ) use ( $isotope ) { return  $isotope === $atom->isotope; }
        );

    }

    /**
     *  Convert Hm to numeric notation via EnumerateMolecule(Hm)
     *
     * @param string $hash
     * @return array
     */
    public static function normalizedHash( $hash )
    {
        // Convert Hm to numeric notation via EnumerateMolecule(Hm)
        return static::normalize( static::enumerate( $hash ) );
    }

    /**
     * This algorithm describes the function EnumerateMolecule(Hm), designed to accept a pseudo-hexadecimal string Hm, and output a collection of decimals representing each character.
     * Molecular hash Hm is presented as a 128 byte (64-character) pseudo-hexadecimal string featuring numbers from 0 to 9 and characters from A to F - a total of 15 unique symbols.
     * To ensure that Hm has an even number of symbols, convert it to Base 17 (adding G as a possible symbol).
     * Map each symbol to integer values as follows:
     * 0   1    2   3   4   5   6   7   8  9  A   B   C   D   E   F   G
     * -8  -7  -6  -5  -4  -3  -2  -1  0   1   2   3   4   5   6   7   8
     *
     * @param string $hash
     * @return array
     */
    protected static function enumerate ( $hash )
    {

        $target = [];
        $mapped = [
            '0' => -8, '1' => -7, '2' => -6, '3' => -5, '4' => -4, '5' => -3, '6' => -2, '7' => -1,
            '8' => 0, '9' => 1, 'a' => 2, 'b' => 3, 'c' => 4, 'd' => 5, 'e' => 6, 'f' => 7, 'g' => 8,
        ];

        foreach ( \str_split( $hash ) as $index => $symbol ) {

            $lower = \strtolower( ( string ) $symbol );

            if ( \array_key_exists( $lower, $mapped ) ) {

                $target[ $index ] = $mapped[ $lower ];

            }

        }

        return $target;

    }

    /**
     * Normalize Hm to ensure that the total sum of all symbols is exactly zero. This ensures that exactly 50% of the WOTS+ key is leaked with each usage, ensuring predictable key safety:
     * The sum of each symbol within Hm shall be presented by m
     * While m0 iterate across that set’s integers as Im:
     * If m0 and Im>-8 , let Im=Im-1
     * If m<0 and Im<8 , let Im=Im+1
     * If m=0, stop the iteration
     *
     * @param array $mappedHashArray
     * @return array
     */
    protected static function normalize ( array $mappedHashArray )
    {

        $total = \array_sum( $mappedHashArray );
        $totalCondition = $total < 0;

        while ( $total < 0 || $total > 0 ) {

            foreach ( $mappedHashArray as $key => $value ) {

                if ( $totalCondition ? $value < 8 : $value > -8 ) {

                    $totalCondition ? [ ++$mappedHashArray[ $key ], ++$total, ] : [ --$mappedHashArray[ $key ], --$total, ];

                    if ( $total === 0 ) {

                        break;

                    }

                }

            }

        }

        return $mappedHashArray;

    }

    /**
     * @param Molecule $molecule
     */
    private static function missing ( Molecule $molecule )
    {
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
