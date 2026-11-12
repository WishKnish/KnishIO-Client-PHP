<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\AtomMeta;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Hashed atom bytes against the JS reference. Every digest literal below was printed by the JS SDK
 * (dist/client.es.mjs, KNISHIO_KCORE=off) for the same inputs: secret generateSecret('fleet-audit-2026-11'),
 * wallets at positions 'aa'×32 / 'bb'×32 (token TEST) and 'cc'×32 (token USER), OTS v2, and
 * createdAt '1700000000000' on every atom, hashed with Atom.hashAtoms.
 */
class SignedBytesJsParityTest extends TestCase {

  private const CREATED_AT = '1700000000000';

  private static function secret (): string {
    return Crypto::generateSecret( 'fleet-audit-2026-11' );
  }

  private static function wallet ( string $token, string $byte ): Wallet {
    return new Wallet( self::secret(), $token, str_repeat( $byte, 32 ), otsVersion: 2 );
  }

  /**
   * @param Atom[] $atoms
   */
  private static function hash ( array $atoms ): string {
    foreach ( $atoms as $atom ) {
      $atom->createdAt = self::CREATED_AT;
    }
    return Atom::hashAtoms( $atoms );
  }

  private static function single ( Atom $atom ): string {
    $atom->index = 0;
    return self::hash( [ $atom ] );
  }

  public function testSecretMatchesJs (): void {
    $this->assertSame( 'd405bf4932289fce650fc090bfbde08f6a712cb38fc2d8414961b455b4337471', Crypto::generateBundleHash( self::secret() ) );
  }

  // A molecule created with version "4" keeps it, and its atoms hash the Version4 view

  public function testVersion4MoleculeHashesLikeJs (): void {
    $source = self::wallet( 'TEST', 'aa' );
    $recipient = self::wallet( 'TEST', 'bb' );
    $molecule = new Molecule( self::secret(), $source, version: '4' );
    $molecule->addAtom( Atom::create( 'V', $source, '-100' ) );
    $molecule->addAtom( Atom::create( 'V', $recipient, '100', 'walletBundle', $recipient->bundle ) );

    $this->assertSame( [ '4', '4' ], array_map( static fn( Atom $atom ) => $atom->version, $molecule->atoms ) );
    $this->assertSame( '043f2e9a40c0817d380g9456f27e670ebb1b2bg9b964eae414618ga93999bcfe', self::hash( $molecule->atoms ) );
  }

  public function testUnknownVersionIsDropped (): void {
    $source = self::wallet( 'TEST', 'aa' );
    $molecule = new Molecule( self::secret(), $source, version: '7' );
    $molecule->addAtom( Atom::create( 'V', $source, '-100' ) );
    $this->assertNull( $molecule->atoms[ 0 ]->version );
  }

  // Policy JSON

  public function testEmptyPolicyHashesLikeJs (): void {
    $user = self::wallet( 'USER', 'cc' );
    $meta = new AtomMeta( [] );
    $meta->addPolicy( [] );

    $this->assertSame( '{"read":{},"write":{}}', $meta->get()[ 'policy' ] );
    $this->assertSame( '010ed4232ffgg7bggb6f1317806a19f14019aaf771a180496010371bc3eg8d37',
      self::single( Atom::create( 'R', $user, null, 'walletBundle', $user->bundle, $meta ) ) );
  }

  public function testPolicyOverMetaEntriesHashesLikeJs (): void {
    $user = self::wallet( 'USER', 'cc' );
    $meta = new AtomMeta( [ 'path/é' => 'x/é', 'name' => 'n' ] );
    $meta->addPolicy( [ 'read' => [], 'write' => [ 'name' => [ 'all' ] ] ] );

    // JS AtomMeta.addPolicy passes Object.keys() of its normalized meta array: entry indices
    $this->assertSame( '{"read":{"0":["all"],"1":["all"]},"write":{"0":["self"],"1":["self"],"name":["all"]}}', $meta->get()[ 'policy' ] );
    $this->assertSame( '0090d16ab8e5968b3b08b3279e1d221750cd20dfd3c28ca9f3e8835fg1cef68c',
      self::single( Atom::create( 'R', $user, null, 'walletBundle', $user->bundle, $meta ) ) );
  }

  public function testPolicyValuesAreNotEscaped (): void {
    $meta = new AtomMeta( [] );
    $meta->addPolicy( [ 'write' => [ 'a/é' => [ 'all' ] ] ] );
    $this->assertSame( '{"write":{"a/é":["all"]},"read":{}}', $meta->get()[ 'policy' ] );
  }

  // Boolean metas

  public function testBooleanMetaHashesLikeJs (): void {
    $user = self::wallet( 'USER', 'cc' );
    $atom = Atom::create( 'M', $user, null, 'walletBundle', $user->bundle, new AtomMeta( [ 'on' => true, 'off' => false, 'n' => 5, 'f' => 1.5 ] ) );

    $this->assertSame( '[{"key":"on","value":"true"},{"key":"off","value":"false"},{"key":"n","value":"5"},{"key":"f","value":"1.5"}]',
      json_encode( $atom->meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ) );
    $this->assertSame( '03cb67043bbd960gb814419c2f5211778e1ga1d75521bc8cd06d077bge3agfc1', self::single( $atom ) );
  }
}
