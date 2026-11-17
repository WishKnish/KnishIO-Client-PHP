<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\AtomMeta;
use WishKnish\KnishIO\Client\KnishIOClient;
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

  // A client's server SDK version reaches its molecules, as JS KnishIOClient.createMolecule passes it

  public function testClientServerSdkVersionReachesItsMolecules (): void {
    foreach ( [ 4 => '4', 3 => null ] as $serverSdkVersion => $expected ) {
      $client = new KnishIOClient( 'https://localhost', serverSdkVersion: $serverSdkVersion );
      $client->setSecret( self::secret() );
      $source = self::wallet( 'USER', 'cc' );
      $molecule = $client->createMolecule( sourceWallet: $source );
      $molecule->addAtom( Atom::create( 'M', $source, null, 'walletBundle', $source->bundle, new AtomMeta( [ 'k' => 'v' ] ) ) );
      $this->assertSame( $expected, $molecule->atoms[ 0 ]->version, "serverSdkVersion $serverSdkVersion" );
    }
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

  // Pre-structured {key, value} metas are stringified as JS Meta.normalizeMeta does

  public function testKeyValueMetaListHashesLikeJs (): void {
    $user = self::wallet( 'USER', 'cc' );
    $atom = Atom::create( 'M', $user, null, 'walletBundle', $user->bundle, new AtomMeta( [
      [ 'key' => 'on', 'value' => true ],
      [ 'key' => 'off', 'value' => false ],
      [ 'key' => 'n', 'value' => 5 ],
      [ 'key' => 'f', 'value' => 1.5 ],
      [ 'key' => 'nil', 'value' => null ],
    ] ) );

    $this->assertSame( '[{"key":"on","value":"true"},{"key":"off","value":"false"},{"key":"n","value":"5"},{"key":"f","value":"1.5"},{"key":"nil","value":null}]',
      json_encode( $atom->meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ) );
    $this->assertSame( '03cb67043bbd960gb814419c2f5211778e1ga1d75521bc8cd06d077bge3agfc1', self::single( $atom ) );
  }

  // Floats are written as JS String() writes them, including integral floats above 2^53

  public function testFloatMetaValuesHashLikeJs (): void {
    $user = self::wallet( 'USER', 'cc' );
    $atom = Atom::create( 'M', $user, null, 'walletBundle', $user->bundle, new AtomMeta( [
      [ 'key' => 'a', 'value' => 0.1 + 0.2 ],
      [ 'key' => 'b', 'value' => 123456789012345680000.0 ],
      [ 'key' => 'c', 'value' => 1e21 ],
      [ 'key' => 'd', 'value' => -0.0 ],
      [ 'key' => 'e', 'value' => 1e-7 ],
    ] ) );

    $this->assertSame( '[{"key":"a","value":"0.30000000000000004"},{"key":"b","value":"123456789012345680000"},{"key":"c","value":"1e+21"},{"key":"d","value":"0"},{"key":"e","value":"1e-7"}]',
      json_encode( $atom->meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ) );
    $this->assertSame( '014e5b9ee56dc286f6cf822040a8ac8794e032ee4g814ac1230256138153a1c7', self::single( $atom ) );
  }

  // Wallet trade rates: JS hashes them only when they are a list (a map has no .length)

  public function testWalletTradeRatesMapIsNotHashedLikeJs (): void {
    $wallet = self::wallet( 'TEST', 'aa' );
    $wallet->tradeRates = [ 'OTHER' => 0.5 ];
    $atom = Atom::create( 'B', $wallet, '100', 'walletBundle', $wallet->bundle );

    $this->assertSame( [], $atom->meta );
    $this->assertSame( '01bb26cf3a554f12b2044gd4eaee92ae87ag8ac33gcebc7d9a3e118df5c498e5', self::single( $atom ) );
  }

  public function testWalletTradeRatesListHashesLikeJs (): void {
    $wallet = self::wallet( 'TEST', 'aa' );
    $wallet->tradeRates = [ [ 'tokenSlug' => 'OTHER', 'amount' => 0.5 ] ];
    $atom = Atom::create( 'B', $wallet, '100', 'walletBundle', $wallet->bundle );

    $this->assertSame( [ [ 'key' => 'tradeRates', 'value' => '[{"tokenSlug":"OTHER","amount":0.5}]' ] ], $atom->meta );
    $this->assertSame( '00gf751g18220gfda39690d5f3741d7f8b6fec883f2e3a708db3ccfa0c65e2g8', self::single( $atom ) );
  }
  // Served unit metas (a JSON string, keys in bytewise order) sign as JS JSON.stringify writes the
  // parsed object: integer-like keys first ascending, then the others in document order, at every
  // depth; empty objects and {"0":…} stay objects; a top-level list stays a list

  public function testServedTokenUnitMetasEncodeLikeJs (): void {
    $encode = static fn( mixed $metas, string $name = 'Unit 1' ): string => \WishKnish\KnishIO\Client\TokenUnit::encodeList( [
      \WishKnish\KnishIO\Client\TokenUnit::createFromGraphQL( [ 'id' => 'u1', 'name' => $name, 'metas' => $metas ] ),
    ] );
    $this->assertSame( '[["u1","Unit 1",{"10":1,"a":2,"x":{"2":0,"b":1,"e":{}},"01":3,"z":{"0":"a"}}]]',
      $encode( '{"a":2,"10":1,"x":{"b":1,"2":0,"e":{}},"01":3,"z":{"0":"a"}}' ) );
    $this->assertSame( '[["u1","Unit 1",{"0":"a","1":"b"}]]', $encode( '{"1":"b","0":"a"}' ) );
    $this->assertSame( '[["u1","Unit 1",[1,2]]]', $encode( '[1,2]' ) );
    foreach ( [ '', 'null', null ] as $metas ) {
      $this->assertSame( '[["u1","u1",{}]]', $encode( $metas, 'u1' ), var_export( $metas, true ) );
    }
    $this->expectException( \JsonException::class );
    $encode( '{bad' );
  }

  // JS TokenUnit.createFromDB(unit.toData()) and createFromGraphQL(unit.toGraphQLResponse()) give
  // back the same unit; toGraphQLResponse's metas is JSON.stringify(metas)
  public function testServedTokenUnitMetasRoundTripLikeJs (): void {
    $cases = [
      '{"a":2,"10":1,"x":{"b":1,"2":0,"e":{}},"01":3,"z":{"0":"a"}}' => '{"10":1,"a":2,"x":{"2":0,"b":1,"e":{}},"01":3,"z":{"0":"a"}}',
      '{"1":"b","0":"a"}' => '{"0":"a","1":"b"}',
    ];
    foreach ( $cases as $served => $js ) {
      $unit = \WishKnish\KnishIO\Client\TokenUnit::createFromGraphQL( [ 'id' => 'u1', 'name' => 'Unit 1', 'metas' => $served ] );
      $signed = \WishKnish\KnishIO\Client\TokenUnit::encodeList( [ $unit ] );
      $this->assertSame( $signed, \WishKnish\KnishIO\Client\TokenUnit::encodeList( [ \WishKnish\KnishIO\Client\TokenUnit::createFromDB( $unit->toData() ) ] ), $served );
      $this->assertSame( $js, $unit->toGraphQLResponse()[ 'metas' ], $served );
      $this->assertSame( $signed, \WishKnish\KnishIO\Client\TokenUnit::encodeList( [ \WishKnish\KnishIO\Client\TokenUnit::createFromGraphQL( $unit->toGraphQLResponse() ) ] ), $served );
    }
  }
}
