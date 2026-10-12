<?php

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\AtomMeta;
use WishKnish\KnishIO\Client\Exception\CryptoException;
use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMalformedException;
use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMismatchException;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\MoleculeStructure;
use WishKnish\KnishIO\Client\Wallet;

/**
 * OTS v2 (docs/OTS_V2_SPECIFICATION.md): key derivation, address, the normalized hash, ML-KEM
 * identities of v2 wallets, and molecule verdicts against the shared vectors; plus a v2 sign/verify
 * round trip that needs no shared file.
 *
 * The shared vectors live in the monorepo (../shared-test-results/ots-v2-vectors.json). A standalone
 * checkout skips the vector test once and still runs the round trip.
 */
class OtsV2VectorsTest extends TestCase {

  private const SECRET = 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890';

  private const EXCEPTIONS = [
    'SignatureMismatch' => MoleculeSignatureMismatchException::class,
    'SignatureMalformed' => MoleculeSignatureMalformedException::class,
  ];

  private static function vectorsPath (): string {
    return dirname( __DIR__, 2 ) . '/shared-test-results/ots-v2-vectors.json';
  }

  private static function address ( string $key ): string {
    return ( new ReflectionMethod( Wallet::class, 'generateAddress' ) )->invoke( null, $key );
  }

  private static function normalizedSymbols ( string $hash ): array {
    $enumerate = new ReflectionMethod( MoleculeStructure::class, 'enumerate' );
    $normalize = new ReflectionMethod( MoleculeStructure::class, 'normalize' );
    return $normalize->invoke( null, $enumerate->invoke( null, $hash ) );
  }

  private static function parse ( array $molecule ): Molecule {
    return Molecule::fromJSON( json_encode( $molecule, JSON_THROW_ON_ERROR ) );
  }

  public function testSharedOtsV2Vectors (): void {
    $path = self::vectorsPath();
    if ( !is_file( $path ) ) {
      $this->markTestSkipped( 'ots-v2-vectors.json not found (standalone checkout)' );
    }
    $vectors = json_decode( file_get_contents( $path ), true, 512, JSON_THROW_ON_ERROR )[ 'vectors' ];

    // Key derivation and address
    foreach ( $vectors[ 'key_derivation' ][ 'tests' ] as $vector ) {
      $name = $vector[ 'name' ];
      [ $secret, $token, $position ] = [ $vector[ 'secret' ], $vector[ 'token' ], $vector[ 'position' ] ];
      $this->assertSame( $vector[ 'expectedKeyV1' ], Wallet::generateKey( $secret, $token, $position ), "$name key v1" );
      $this->assertSame( $vector[ 'expectedKeyV2' ], Wallet::generateKey( $secret, $token, $position, 2 ), "$name key v2" );
      $this->assertSame( $vector[ 'expectedAddressV1' ], self::address( $vector[ 'expectedKeyV1' ] ), "$name address v1" );
      $this->assertSame( $vector[ 'expectedAddressV2' ], self::address( $vector[ 'expectedKeyV2' ] ), "$name address v2" );
      $this->assertSame( $vector[ 'expectedAddressV2' ], ( new Wallet( $secret, $token, $position, otsVersion: 2 ) )->address, "$name v2 wallet" );
    }
    try {
      new Wallet( 'x', otsVersion: 3 );
      $this->fail( 'OTS version 3 was accepted' );
    }
    catch ( CryptoException $e ) {
      $this->assertSame( 'KnishIO: unsupported OTS version 3; expected 1 or 2.', $e->getMessage() );
    }

    // Normalized hash
    foreach ( $vectors[ 'signature' ][ 'tests' ] as $vector ) {
      $this->assertSame( $vector[ 'expectedNormalized' ], self::normalizedSymbols( $vector[ 'molecularHash' ] ), $vector[ 'name' ] );
    }

    // ML-KEM identities of v2 wallets
    foreach ( $vectors[ 'mlkem_identity' ][ 'tests' ] as $vector ) {
      $source = current( array_filter( $vectors[ 'key_derivation' ][ 'tests' ], static fn ( $k ) => $k[ 'name' ] === $vector[ 'keyFrom' ] ) );
      foreach ( [ 1024 => 'expectedMlKem1024Pubkey', 768 => 'expectedMlKem768Pubkey' ] as $set => $field ) {
        $wallet = new Wallet( $source[ 'secret' ], $source[ 'token' ], $source[ 'position' ], mlKemParameterSet: $set, otsVersion: 2 );
        $this->assertSame( $vector[ $field ], $wallet->pubkey, "{$vector['name']} ML-KEM-$set" );
      }
    }

    // Molecule verdicts
    foreach ( $vectors[ 'molecules' ][ 'tests' ] as $vector ) {
      $molecule = self::parse( $vector[ 'molecule' ] );
      if ( $vector[ 'expected' ] === 'verified' ) {
        $molecule->check();
        $this->addToAssertionCount( 1 );
        continue;
      }
      try {
        $molecule->check();
        $this->fail( "{$vector['name']} verified; expected {$vector['expected']}" );
      }
      catch ( MoleculeSignatureMalformedException|MoleculeSignatureMismatchException $e ) {
        $this->assertInstanceOf( self::EXCEPTIONS[ $vector[ 'expected' ] ], $e, $vector[ 'name' ] );
      }
    }

    // Re-signing reproduces every otsFragment
    $user = current( array_filter( $vectors[ 'key_derivation' ][ 'tests' ], static fn ( $k ) => $k[ 'name' ] === 'hex_secret_user' ) );
    foreach ( [ 'genuine_v2', 'genuine_v1_control' ] as $name ) {
      $vector = current( array_filter( $vectors[ 'molecules' ][ 'tests' ], static fn ( $t ) => $t[ 'name' ] === $name ) );
      $parsed = self::parse( $vector[ 'molecule' ] );
      $sourceWallet = new Wallet( $user[ 'secret' ], 'USER', $user[ 'position' ], otsVersion: $vector[ 'otsVersion' ] );
      $molecule = new Molecule( $user[ 'secret' ], $sourceWallet, $sourceWallet, $parsed->cellSlug );
      $molecule->atoms = $parsed->atoms;
      $molecule->sign();
      $this->assertSame(
        array_column( $vector[ 'molecule' ][ 'atoms' ], 'otsFragment' ),
        array_map( static fn ( $atom ) => $atom->otsFragment, $molecule->atoms ),
        "re-signing $name"
      );
    }

    // Malformed declared hash: ots() alone refuses it
    foreach ( $vectors[ 'malformed_hash' ][ 'tests' ] as $vector ) {
      $check = new CheckMolecule( self::parse( $vector[ 'molecule' ] ) );
      try {
        $check->ots();
        $this->fail( "{$vector['name']} accepted" );
      }
      catch ( MoleculeSignatureMalformedException|MoleculeSignatureMismatchException $e ) {
        $this->assertInstanceOf( self::EXCEPTIONS[ $vector[ 'expected' ] ], $e, $vector[ 'name' ] );
      }
    }
  }

  public function testOtsV2SignVerifyRoundTrip (): void {
    $sourceWallet = new Wallet( self::SECRET, 'USER', otsVersion: 2 );
    $molecule = new Molecule( self::SECRET, $sourceWallet, null, 'otsv2test' );
    $this->assertSame( 2, $molecule->remainderWallet()->otsVersion );
    $molecule->addAtom( Atom::create( 'M', $sourceWallet, null, 'walletBundle', $sourceWallet->bundle, new AtomMeta( [ 'otsVersion' => '2' ] ) ) );
    $molecule->addContinuIdAtom();
    $molecule->sign( compressed: true );

    $signature = implode( '', array_map( static fn ( $atom ) => $atom->otsFragment, $molecule->atoms ) );
    $this->assertSame( 5464, strlen( $signature ) );
    $molecule->check();

    // One base64 character flipped in the first fragment
    $fragment = $molecule->atoms[ 0 ]->otsFragment;
    $molecule->atoms[ 0 ]->otsFragment = ( $fragment[ 0 ] === 'A' ? 'B' : 'A' ) . substr( $fragment, 1 );
    $this->expectException( MoleculeSignatureMismatchException::class );
    $molecule->check();
  }
}
