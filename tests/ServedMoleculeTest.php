<?php

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\MoleculeStructure;

/**
 * CheckMolecule::verifyFromServerData() against molecules exactly as the GraphQL API serves them
 * (tests/fixtures/served-molecules.json), and Version4 atom hashing against the shared vectors
 * (tests/fixtures/version4-hash-vectors.json).
 */
class ServedMoleculeTest extends TestCase {

  private const MISMATCH = 'The molecular hash does not match';

  private static function fixture ( string $name ): array {
    $path = __DIR__ . '/fixtures/' . $name;
    return json_decode( file_get_contents( $path ), true, 512, JSON_THROW_ON_ERROR );
  }

  private static function served ( string $key ): array {
    return self::fixture( 'served-molecules.json' )[ $key ];
  }

  private static function withVersion ( array $molecule, ?string $version ): array {
    foreach ( $molecule[ 'atoms' ] as $i => $atom ) {
      $molecule[ 'atoms' ][ $i ][ 'version' ] = $version;
    }
    return $molecule;
  }

  public function testV1AndV2Verify (): void {
    foreach ( [ 'v1', 'v2' ] as $key ) {
      $molecule = self::served( $key );
      $result = CheckMolecule::verifyFromServerData( $molecule );
      $this->assertSame( [
        'molecularHash' => $molecule[ 'molecularHash' ],
        'verified' => true,
        'error' => null,
      ], $result, $key );
    }
  }

  public function testV2tsVerifiesThroughVersion4Retry (): void {
    $molecule = self::served( 'v2ts' );
    foreach ( $molecule[ 'atoms' ] as $atom ) {
      $this->assertNull( $atom[ 'version' ] ?? null );
    }
    $result = CheckMolecule::verifyFromServerData( $molecule );
    $this->assertTrue( $result[ 'verified' ], (string) $result[ 'error' ] );
    $this->assertNull( $result[ 'error' ] );
  }

  public function testV2tsWithVersion4VerifiesDirectly (): void {
    $result = CheckMolecule::verifyFromServerData( self::withVersion( self::served( 'v2ts' ), '4' ) );
    $this->assertTrue( $result[ 'verified' ], (string) $result[ 'error' ] );
  }

  public function testV3rustServedWithVersionVerifies (): void {
    $molecule = self::served( 'v3rust' );
    foreach ( $molecule[ 'atoms' ] as $atom ) {
      $this->assertSame( '3', $atom[ 'version' ] );
    }
    $result = CheckMolecule::verifyFromServerData( $molecule );
    $this->assertTrue( $result[ 'verified' ], (string) $result[ 'error' ] );
  }

  public function testV3rustWithoutVersionsVerifiesThroughVersion3Retry (): void {
    $result = CheckMolecule::verifyFromServerData( self::withVersion( self::served( 'v3rust' ), null ) );
    $this->assertTrue( $result[ 'verified' ], (string) $result[ 'error' ] );
  }

  public function testTamperedV3rustFailsWithHashMismatch (): void {
    $tampered = self::served( 'v3rust' );
    $tampered[ 'atoms' ][ 0 ][ 'metaId' ] = 'rust-pre-tampered';
    foreach ( [ 'served versions' => $tampered, 'no versions' => self::withVersion( $tampered, null ) ] as $label => $molecule ) {
      $result = CheckMolecule::verifyFromServerData( $molecule );
      $this->assertFalse( $result[ 'verified' ], $label );
      $this->assertSame( self::MISMATCH, $result[ 'error' ], $label );
      $this->assertSame( $tampered[ 'molecularHash' ], $result[ 'molecularHash' ], $label );
    }
  }

  public function testServedVersionDisablesRetry (): void {
    $result = CheckMolecule::verifyFromServerData( self::withVersion( self::served( 'v1' ), '4' ) );
    $this->assertFalse( $result[ 'verified' ] );
    $this->assertSame( self::MISMATCH, $result[ 'error' ] );
  }

  public function testMalformedInputIsNotVerified (): void {
    foreach ( [ 'not an object' => 'x', 'no atoms' => [ 'molecularHash' => 'abc' ], 'atoms not a list' => [ 'atoms' => 3 ] ] as $label => $data ) {
      $result = CheckMolecule::verifyFromServerData( $data );
      $this->assertFalse( $result[ 'verified' ], $label );
      $this->assertIsString( $result[ 'error' ], $label );
    }
  }

  public function testCreatedAtNormalization (): void {
    $this->assertSame( '1792008157000', CheckMolecule::toSignedTimestamp( '2026-10-14T20:02:37+00:00' ) );
    $this->assertSame( '1792008157110', CheckMolecule::toSignedTimestamp( '2026-10-14T20:02:37.110Z' ) );
    $this->assertSame( '1792008157110', CheckMolecule::toSignedTimestamp( 1792008157110 ) );
    $this->assertSame( '1792008157110', CheckMolecule::toSignedTimestamp( '1792008157110' ) );
    $this->assertSame( 'not-a-date', CheckMolecule::toSignedTimestamp( 'not-a-date' ) );
    $this->assertNull( CheckMolecule::toSignedTimestamp( null ) );
    $this->assertNull( CheckMolecule::toSignedTimestamp( '' ) );
    $this->assertSame( '1792008157110', CheckMolecule::toSignedTimestamp( '2026-10-14T22:02:37.110987+02:00' ) );
  }

  public function testFromServerDataMapping (): void {
    $molecule = CheckMolecule::fromServerData( [
      'molecularHash' => 'h',
      'bundleHash' => 'b',
      'createdAt' => '2026-10-14T20:02:37.110Z',
      'atoms' => [ [
        'position' => 'p',
        'walletAddress' => 'w',
        'isotope' => 'M',
        'token' => 'USER',
        'value' => 5,
        'index' => 0,
        'createdAt' => 1792008157000,
        'version' => 4,
        'metasJson' => '{"a":1,"b":true,"c":null,"d":"x","e":1.5}',
      ] ],
    ] );
    $this->assertSame( 'b', $molecule->bundle );
    $this->assertSame( '1792008157110', $molecule->createdAt );
    $atom = $molecule->atoms[ 0 ];
    $this->assertSame( 'USER', $atom->token );
    $this->assertSame( '5', $atom->value );
    $this->assertSame( '1792008157000', $atom->createdAt );
    $this->assertSame( '4', $atom->version );
    $this->assertSame( [
      [ 'key' => 'a', 'value' => '1' ],
      [ 'key' => 'b', 'value' => 'true' ],
      [ 'key' => 'c', 'value' => null ],
      [ 'key' => 'd', 'value' => 'x' ],
      [ 'key' => 'e', 'value' => '1.5' ],
    ], $atom->meta );
    $broken = CheckMolecule::fromServerData( [ 'molecularHash' => 'h', 'atoms' => [ [ 'isotope' => 'M', 'index' => 0, 'metasJson' => '{' ] ] ] );
    $this->assertSame( [], $broken->atoms[ 0 ]->meta );
  }

  public static function version4Vectors (): array {
    $cases = [];
    foreach ( self::fixture( 'version4-hash-vectors.json' )[ 'cases' ] as $case ) {
      $cases[ $case[ 'name' ] ] = [ $case ];
    }
    return $cases;
  }

  #[DataProvider( 'version4Vectors' )]
  public function testVersion4HashVector ( array $case ): void {
    $atoms = MoleculeStructure::toObject( [ 'atoms' => $case[ 'atoms' ] ] )->atoms;
    $this->assertSame( $case[ 'expectedHash' ], Atom::hashAtoms( $atoms ), 'hashAtoms' );
    $this->assertSame( $case[ 'viewJson' ], Atom::version4ViewJson( $atoms ), 'version4ViewJson' );

    // Molecule::fromJSON carries the atom versions too
    $molecule = Molecule::fromJSON( json_encode( [
      'molecularHash' => $case[ 'expectedHash' ],
      'createdAt' => '1792008157000',
      'atoms' => $case[ 'atoms' ],
    ], JSON_THROW_ON_ERROR ) );
    $this->assertSame( $case[ 'expectedHash' ], Atom::hashAtoms( $molecule->atoms ), 'Molecule::fromJSON' );
  }

  public function testLegacyCaseNames (): void {
    $names = array_column( self::fixture( 'version4-hash-vectors.json' )[ 'legacyCases' ], 'name' );
    $this->assertSame( [ 'legacy-empty-strings', 'legacy-nulls' ], $names );
  }

  public static function legacyVectors (): array {
    $cases = [];
    foreach ( self::fixture( 'version4-hash-vectors.json' )[ 'legacyCases' ] as $case ) {
      $cases[ $case[ 'name' ] ] = [ $case ];
    }
    return $cases;
  }

  #[DataProvider( 'legacyVectors' )]
  public function testLegacyHashVector ( array $case ): void {
    $atoms = MoleculeStructure::toObject( [ 'atoms' => $case[ 'atoms' ] ] )->atoms;
    foreach ( $atoms as $atom ) {
      $this->assertNull( $atom->version );
    }
    $this->assertSame( $case[ 'expectedHash' ], Atom::hashAtoms( $atoms ), 'hashAtoms' );

    $molecule = Molecule::fromJSON( json_encode( [
      'molecularHash' => $case[ 'expectedHash' ],
      'createdAt' => '1792008157000',
      'atoms' => $case[ 'atoms' ],
    ], JSON_THROW_ON_ERROR ) );
    $this->assertSame( $case[ 'expectedHash' ], Atom::hashAtoms( $molecule->atoms ), 'Molecule::fromJSON' );
  }
}
