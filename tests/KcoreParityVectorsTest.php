<?php

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Libraries\Kcore;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * kcore parity: full signed molecules (metadata, value transfer, token creation, wallet creation) at
 * OTS v1 and v2, with unique and colliding createdAt values, against the shared vectors that
 * scripts/gen-kcore-parity-vectors.mjs generates with the JS SDK (vendored byte-identically to
 * tests/fixtures/kcore-parity-vectors.json by scripts/sync-cross-sdk-fixtures.sh).
 *
 * Every case must verify (molecular hash + OTS signature) and re-sign to the same molecular hash and
 * otsFragments byte for byte under whichever backend runs the suite. CI runs it twice: with
 * KNISHIO_KCORE=require (libkcore must load and carry the run) and KNISHIO_KCORE=off (pure PHP).
 * A missing fixture fails; it is never skipped.
 */
class KcoreParityVectorsTest extends TestCase {

  private const CASES = 16;

  private static function vectorsPath (): string {
    return __DIR__ . '/fixtures/kcore-parity-vectors.json';
  }

  private static function parse ( array $molecule ): Molecule {
    return Molecule::fromJSON( json_encode( $molecule, JSON_THROW_ON_ERROR ) );
  }

  public static function parityVectors (): array {
    $cases = [];
    foreach ( json_decode( file_get_contents( self::vectorsPath() ), true, 512, JSON_THROW_ON_ERROR )[ 'vectors' ][ 'molecules' ][ 'tests' ] as $case ) {
      $cases[ $case[ 'name' ] ] = [ $case ];
    }
    return $cases;
  }

  public function testAllCasesArePresent (): void {
    $this->assertFileExists( self::vectorsPath(), 'kcore-parity-vectors.json is missing; run scripts/sync-cross-sdk-fixtures.sh' );
    $this->assertCount( self::CASES, self::parityVectors() );
  }

  /** The backend under test is whatever KNISHIO_KCORE selects; prove it is the one asked for. */
  public function testBackendIsTheOneRequested (): void {
    Kcore::reset();
    $mode = getenv( 'KNISHIO_KCORE' );
    if ( $mode === 'require' ) {
      $this->assertTrue( Kcore::available(), 'KNISHIO_KCORE=require but libkcore did not load' );
      $this->assertNotNull( Kcore::libraryPath() );
      return;
    }
    if ( $mode === 'off' ) {
      $this->assertFalse( Kcore::available(), 'KNISHIO_KCORE=off but libkcore is in use' );
      return;
    }
    $this->assertIsBool( Kcore::available() );
  }

  #[DataProvider( 'parityVectors' )]
  public function testVerifyAndResign ( array $case ): void {
    Kcore::reset();
    $expected = $case[ 'expected' ];

    // (a) verify: the declared hash is the hash of the atoms, and the OTS matches the signing wallet
    $parsed = self::parse( $case[ 'molecule' ] );
    $this->assertSame( $expected[ 'molecularHash' ], $parsed->molecularHash, 'declared hash' );
    $this->assertSame( $expected[ 'molecularHash' ], Atom::hashAtoms( $parsed->atoms ), 'recomputed hash' );
    $check = new CheckMolecule( $parsed );
    $check->molecularHash();
    $check->ots();

    // (b) re-sign from the case's source wallet, keeping the vector's atoms and createdAt values
    $parsed = self::parse( $case[ 'molecule' ] );
    $sourceWallet = new Wallet( $case[ 'secret' ], $case[ 'sourceToken' ], $case[ 'sourcePosition' ], otsVersion: $case[ 'otsVersion' ] );
    $molecule = new Molecule( $case[ 'secret' ], $sourceWallet, $sourceWallet, $parsed->cellSlug );
    $molecule->createdAt = $parsed->createdAt;
    $molecule->atoms = $parsed->atoms;
    // clear the parsed fragments so every compared byte comes from this signing
    foreach ( $molecule->atoms as $atom ) {
      $atom->otsFragment = null;
    }
    $molecule->sign();
    $this->assertSame( $expected[ 'molecularHash' ], $molecule->molecularHash, 're-signed hash' );
    $this->assertSame(
      array_column( $case[ 'molecule' ][ 'atoms' ], 'createdAt' ),
      array_map( static fn ( Atom $atom ) => $atom->createdAt, $molecule->atoms ),
      'atom createdAt kept'
    );
    $this->assertCount( count( $expected[ 'otsFragments' ] ), $molecule->atoms, 'atom count' );
    foreach ( $molecule->atoms as $i => $atom ) {
      $this->assertSame( $expected[ 'otsFragments' ][ $i ], $atom->otsFragment, "otsFragment[$i]" );
    }
  }
}
