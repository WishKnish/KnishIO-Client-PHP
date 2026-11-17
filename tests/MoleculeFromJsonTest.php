<?php

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\TestCase;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Molecule::fromJSON() with and without createdAt on the molecule and its atoms.
 */
class MoleculeFromJsonTest extends TestCase {

  private static function atom ( int $index, ?string $createdAt ): array {
    $atom = [
      'position' => str_repeat( 'a', 64 ),
      'walletAddress' => str_repeat( 'b', 64 ),
      'isotope' => 'M',
      'token' => 'USER',
      'value' => null,
      'meta' => [ [ 'key' => 'k', 'value' => 'v' ] ],
      'index' => $index,
    ];
    if ( $createdAt !== null ) {
      $atom[ 'createdAt' ] = $createdAt;
    }
    return $atom;
  }

  public function testMoleculeAndAtomsWithoutCreatedAtKeepTheirDefaults (): void {
    $molecule = Molecule::fromJSON( json_encode( [
      'molecularHash' => str_repeat( '0', 64 ),
      'atoms' => [ self::atom( 0, null ) ],
    ], JSON_THROW_ON_ERROR ) );

    $this->assertMatchesRegularExpression( '/^\d{13}$/', $molecule->createdAt );
    $this->assertMatchesRegularExpression( '/^\d{13}$/', $molecule->atoms[ 0 ]->createdAt );
  }

  public function testServedCreatedAtIsKept (): void {
    $molecule = Molecule::fromJSON( json_encode( [
      'molecularHash' => str_repeat( '0', 64 ),
      'createdAt' => '1792008157000',
      'atoms' => [ self::atom( 0, '1792008157110' ) ],
    ], JSON_THROW_ON_ERROR ) );

    $this->assertSame( '1792008157000', $molecule->createdAt );
    $this->assertSame( '1792008157110', $molecule->atoms[ 0 ]->createdAt );
  }

  // The molecule's version is kept, as JS Molecule.fromJSON does, so atoms added later carry it

  public function testMoleculeVersionReachesAddedAtoms (): void {
    foreach ( [ '4' => '4', '7' => null ] as $version => $expected ) {
      $molecule = Molecule::fromJSON( json_encode( [
        'molecularHash' => str_repeat( '0', 64 ),
        'version' => (string) $version,
        'atoms' => [ self::atom( 0, null ) ],
      ], JSON_THROW_ON_ERROR ) );
      $wallet = new Wallet( str_repeat( 'f', 2048 ), 'USER', str_repeat( 'cc', 32 ) );
      $molecule->addAtom( Atom::create( 'M', $wallet, null, 'walletBundle', $wallet->bundle ) );

      $this->assertSame( $expected, $molecule->atoms[ 1 ]->version, "version $version" );
    }
  }

  // toJSON keeps a version the SDK knows, as JS toJSON does; an unknown one is dropped

  public function testToJsonKeepsKnownVersion (): void {
    foreach ( [ '4' => '4', '7' => null ] as $version => $expected ) {
      $molecule = Molecule::fromJSON( json_encode( [
        'molecularHash' => str_repeat( '0', 64 ),
        'version' => (string) $version,
        'atoms' => [ self::atom( 0, null ) ],
      ], JSON_THROW_ON_ERROR ) );
      $data = json_decode( $molecule->toJSON(), true, 512, JSON_THROW_ON_ERROR );

      if ( $expected === null ) {
        $this->assertArrayNotHasKey( 'version', $data, "version $version" );
      } else {
        $this->assertSame( $expected, $data[ 'version' ], "version $version" );
      }
    }
  }

  // toJSON carries cellSlugOrigin before version, as JS toJSON's validation context does; fromJSON reads it back

  public function testCellSlugOriginRoundTrips (): void {
    $molecule = new Molecule( secret: str_repeat( 'f', 2048 ), cellSlug: 'origin-cell' );
    $data = json_decode( $molecule->toJSON(), true, 512, JSON_THROW_ON_ERROR );
    $this->assertArrayHasKey( 'cellSlugOrigin', $data );
    $this->assertSame( 'origin-cell', $data[ 'cellSlugOrigin' ] );

    $read = Molecule::fromJSON( json_encode( [
      'molecularHash' => str_repeat( '0', 64 ),
      'cellSlug' => 'current-cell',
      'cellSlugOrigin' => 'origin-cell',
      'version' => '4',
      'atoms' => [ self::atom( 0, null ) ],
    ], JSON_THROW_ON_ERROR ) );
    $data = json_decode( $read->toJSON(), true, 512, JSON_THROW_ON_ERROR );
    $keys = array_keys( $data );
    $this->assertSame( 'current-cell', $data[ 'cellSlug' ] );
    $this->assertArrayHasKey( 'cellSlugOrigin', $data );
    $this->assertSame( 'origin-cell', $data[ 'cellSlugOrigin' ] );
    $this->assertSame( '4', $data[ 'version' ] );
    $this->assertSame( array_search( 'cellSlugOrigin', $keys, true ) + 1, array_search( 'version', $keys, true ) );
  }
  // JS toJSON omits cellSlugOrigin only for a molecule read from JSON without it (undefined);
  // a constructed molecule and an explicit null both write the key with null

  public function testCellSlugOriginKeyFollowsJs (): void {
    $read = Molecule::fromJSON( json_encode( [
      'molecularHash' => str_repeat( '0', 64 ),
      'atoms' => [ self::atom( 0, null ) ],
    ], JSON_THROW_ON_ERROR ) );
    $data = json_decode( $read->toJSON(), true, 512, JSON_THROW_ON_ERROR );
    $this->assertArrayNotHasKey( 'cellSlugOrigin', $data );

    $built = new Molecule( secret: str_repeat( 'f', 2048 ) );
    $data = json_decode( $built->toJSON(), true, 512, JSON_THROW_ON_ERROR );
    $this->assertArrayHasKey( 'cellSlugOrigin', $data );
    $this->assertNull( $data[ 'cellSlugOrigin' ] );

    $read = Molecule::fromJSON( json_encode( [
      'molecularHash' => str_repeat( '0', 64 ),
      'cellSlugOrigin' => null,
      'atoms' => [ self::atom( 0, null ) ],
    ], JSON_THROW_ON_ERROR ) );
    $data = json_decode( $read->toJSON(), true, 512, JSON_THROW_ON_ERROR );
    $this->assertArrayHasKey( 'cellSlugOrigin', $data );
    $this->assertNull( $data[ 'cellSlugOrigin' ] );
  }
}
