<?php

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\TestCase;
use WishKnish\KnishIO\Client\Molecule;

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
}
