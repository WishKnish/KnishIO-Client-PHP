<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\AtomMeta;
use WishKnish\KnishIO\Client\Exception\MoleculeAtomsMissingException;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * CheckMolecule::verify() must run the ContinuID check, as the JS reference does
 * (CheckMolecule.js verify(): molecularHash → ots → batchId → continuId → …): a molecule
 * whose first atom spends the USER token must carry a ContinuID `I` atom.
 */
class CheckMoleculeContinuIdTest extends TestCase {

  /**
   * A signed USER-token metadata molecule. Its hash and OTS signature are valid, so any
   * rejection comes from the ContinuID rule alone.
   */
  private function signedUserMolecule ( bool $withContinuId ): array {
    $secret = Crypto::generateSecret( 'continuid-check-test-seed', 2048 );
    $sourceWallet = new Wallet( $secret, 'USER' );
    $molecule = new Molecule( $secret, $sourceWallet );

    $molecule->addAtom( Atom::create(
      'M',
      $sourceWallet,
      null,
      'ContinuIdTestType',
      'ContinuIdTestId',
      new AtomMeta( [ 'name' => 'continuid' ] )
    ) );
    if ( $withContinuId ) {
      $molecule->addContinuIdAtom();
    }
    $molecule->sign();

    return [ $molecule, $sourceWallet ];
  }

  public function testUserMoleculeWithoutContinuIdAtomIsRejected (): void {
    [ $molecule, $sourceWallet ] = $this->signedUserMolecule( false );

    $this->assertSame( 'USER', $molecule->atoms[ 0 ]->token );
    $this->assertCount( 0, $molecule->getIsotopes( 'I' ) );

    $this->expectException( MoleculeAtomsMissingException::class );
    $this->expectExceptionMessage( 'Missing atom ContinuID' );
    ( new CheckMolecule( $molecule ) )->verify( $sourceWallet );
  }

  public function testUserMoleculeWithContinuIdAtomPasses (): void {
    [ $molecule, $sourceWallet ] = $this->signedUserMolecule( true );

    $this->assertCount( 1, $molecule->getIsotopes( 'I' ) );

    ( new CheckMolecule( $molecule ) )->verify( $sourceWallet );
    $this->addToAssertionCount( 1 );
  }
}
