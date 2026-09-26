<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMismatchException;
use WishKnish\KnishIO\Client\Molecule;

/**
 * The OTS address check must compare the recovered address only against atoms[0].walletAddress.
 *
 * tests/fixtures/signing-wallet-forgery.json holds two molecules built by the published JS SDK
 * 1.2.1: `genuine`, signed normally by wallet B, and `forged`, whose first atom claims victim
 * wallet A's address while carrying B's OTS signature and a `signingWallet` meta naming B.
 * A verifier that honours that meta attributes B's signature to A.
 */
class SigningWalletForgeryTest extends TestCase {

  private function fixture (): array {
    return json_decode(
      file_get_contents( __DIR__ . '/fixtures/signing-wallet-forgery.json' ),
      true,
      512,
      JSON_THROW_ON_ERROR
    );
  }

  private function load ( array $molecule ): Molecule {
    return Molecule::fromJSON( json_encode( $molecule, JSON_THROW_ON_ERROR ) );
  }

  public function testGenuineMoleculePassesCheck (): void {
    $molecule = $this->load( $this->fixture()[ 'genuine' ] );

    $molecule->check();
    $this->addToAssertionCount( 1 );
  }

  public function testForgedMoleculeClaimsTheVictimAddress (): void {
    $fixture = $this->fixture();
    $molecule = $this->load( $fixture[ 'forged' ] );

    $this->assertSame( $fixture[ 'victimAddress' ], $molecule->atoms[ 0 ]->walletAddress );
    $this->assertNotSame( $fixture[ 'attackerAddress' ], $fixture[ 'victimAddress' ] );
  }

  public function testForgedMoleculeFailsCheckWithSignatureMismatch (): void {
    $molecule = $this->load( $this->fixture()[ 'forged' ] );

    $this->expectException( MoleculeSignatureMismatchException::class );
    $molecule->check();
  }
}
