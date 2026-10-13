<?php

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\AtomMeta;
use WishKnish\KnishIO\Client\AuthToken;
use WishKnish\KnishIO\Client\Exception\CryptoException;
use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMalformedException;
use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMismatchException;
use WishKnish\KnishIO\Client\Exception\WalletSignatureException;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\MoleculeStructure;
use WishKnish\KnishIO\Client\Response\ResponseWalletList;
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

  private const CLIENT_POSITION = 'b0b1b2b3b4b5b6b7b8b9babbbcbdbebfb0b1b2b3b4b5b6b7b8b9babbbcbdbebf';

  private static function clientSecret (): string {
    return Crypto::generateSecret( 'ots-v2-client-level', 2048 );
  }

  private static function ledgerWallet ( string $address ): Wallet {
    $wallet = new Wallet( null, 'USER', self::CLIENT_POSITION );
    $wallet->address = $address;
    return $wallet;
  }

  private static function continuIdBody ( ?string $address ): string {
    return json_encode( [ 'data' => [ 'ContinuId' => $address === null ? null : [
      'type' => 'regular',
      'address' => $address,
      'bundleHash' => Crypto::generateBundleHash( self::clientSecret() ),
      'tokenSlug' => 'USER',
      'position' => self::CLIENT_POSITION,
      'batchId' => null,
      'characters' => 'BASE64',
      'pubkey' => null,
      'amount' => '0',
      'createdAt' => '2026-10-12T00:00:00Z',
    ] ] ], JSON_THROW_ON_ERROR );
  }

  private static function proposeBody ( bool $accepted ): string {
    return json_encode( [ 'data' => [ 'ProposeMolecule' => [
      'molecularHash' => str_repeat( 'a', 64 ),
      'height' => 0,
      'depth' => 0,
      'status' => $accepted ? 'accepted' : 'rejected',
      'reason' => $accepted ? null : 'offline',
      'payload' => $accepted ? json_encode( [
        'token' => 'offline-auth-token',
        'expiresAt' => (string) ( time() + 3600 ),
        'pubkey' => 'offline-validator-pubkey',
        'encrypt' => false,
      ], JSON_THROW_ON_ERROR ) : null,
      'createdAt' => '2026-10-12T00:00:00Z',
    ] ] ], JSON_THROW_ON_ERROR );
  }

  /**
   * @param string[] $bodies
   *
   * @return array{0: KnishIOClient, 1: RecordingHttpClient}
   */
  private static function clientAt ( int $otsVersion, array $bodies ): array {
    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', $bodies );
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http, otsVersion: $otsVersion );
    $client->setSecret( self::clientSecret() );
    return [ $client, $http ];
  }

  private static function addressAt ( int $otsVersion, string $token = 'USER', string $position = self::CLIENT_POSITION ): string {
    return self::address( Wallet::generateKey( self::clientSecret(), $token, $position, $otsVersion ) );
  }

  public function testDetectOtsVersionEitherPreferredOrder (): void {
    $secret = self::clientSecret();
    foreach ( [ 1, 2 ] as $preferred ) {
      $this->assertSame( 1, Wallet::detectOtsVersion( $secret, 'USER', self::CLIENT_POSITION, self::addressAt( 1 ), $preferred ) );
      $this->assertSame( 2, Wallet::detectOtsVersion( $secret, 'USER', self::CLIENT_POSITION, self::addressAt( 2 ), $preferred ) );
      $this->assertSame( 2, Wallet::detectOtsVersion( $secret, 'USER', self::CLIENT_POSITION, strtoupper( self::addressAt( 2 ) ), $preferred ) );
      $this->assertNull( Wallet::detectOtsVersion( $secret, 'USER', self::CLIENT_POSITION, bin2hex( random_bytes( 32 ) ), $preferred ) );
    }
  }

  public function testSetOtsVersionOnKeylessAndKeyedWallets (): void {
    $this->assertSame( 2, self::ledgerWallet( self::addressAt( 2 ) )->setOtsVersion( 2 )->otsVersion );

    $keyed = new Wallet( self::clientSecret(), 'USER', self::CLIENT_POSITION );
    $this->assertSame( 1, $keyed->setOtsVersion( 1 )->otsVersion );
    $this->expectException( CryptoException::class );
    $this->expectExceptionMessage( 'KnishIO: cannot change the OTS version of a keyed wallet.' );
    $keyed->setOtsVersion( 2 );
  }

  public function testClientOtsVersionIsValidated (): void {
    [ $client ] = self::clientAt( 2, [] );
    $this->assertSame( 2, $client->getOtsVersion() );
    $this->assertSame( 1, $client->setOtsVersion( 1 )->getOtsVersion() );
    $this->expectException( CryptoException::class );
    $this->expectExceptionMessage( 'KnishIO: unsupported OTS version 3; expected 1 or 2.' );
    $client->setOtsVersion( 3 );
  }

  public function testClientAtV2CreatesV2SourceWallet (): void {
    [ $client ] = self::clientAt( 2, [ self::continuIdBody( null ) ] );
    $sourceWallet = $client->getSourceWallet();
    $this->assertSame( 2, $sourceWallet->otsVersion );
    $this->assertSame( 8192, strlen( $sourceWallet->key ) );
  }

  public function testClientAtV2SignsAuthorizationFromV2AuthWallet (): void {
    [ $client, $http ] = self::clientAt( 2, [ self::continuIdBody( null ), self::proposeBody( true ) ] );
    $this->assertTrue( $client->requestProfileAuthToken( self::clientSecret(), false )->success() );
    $authWallet = $client->getAuthToken()->getWallet();
    $this->assertSame( 'AUTH', $authWallet->token );
    $this->assertSame( 2, $authWallet->otsVersion );
    $this->assertSame( 8192, strlen( $authWallet->key ) );
    $this->assertCount( 2, $http->requests );
  }

  public function testClientAtV2CreatesV2Wallet (): void {
    [ $client, $http ] = self::clientAt( 2, [ self::continuIdBody( null ), self::proposeBody( false ) ] );
    $client->createWallet( 'OTSV2' );
    $request = json_decode( (string) $http->requests[ 1 ]->getBody(), true, 512, JSON_THROW_ON_ERROR );
    $atoms = array_values( array_filter( $request[ 'variables' ][ 'molecule' ][ 'atoms' ], static fn ( array $atom ) => $atom[ 'isotope' ] === 'C' ) );
    $this->assertCount( 1, $atoms );
    $metas = array_column( $atoms[ 0 ][ 'meta' ], 'value', 'key' );
    $this->assertSame( self::addressAt( 2, 'OTSV2', $metas[ 'walletPosition' ] ), $metas[ 'walletAddress' ] );
  }

  public function testLedgerWalletIsBoundToTheVersionOfItsAddress (): void {
    [ $client ] = self::clientAt( 1, [ self::continuIdBody( self::addressAt( 2 ) ) ] );
    $sourceWallet = $client->getSourceWallet();
    $this->assertSame( 2, $sourceWallet->otsVersion );
    $this->assertSame( self::CLIENT_POSITION, $sourceWallet->position );
  }

  public function testLedgerWalletNotDerivedFromTheSecretThrows (): void {
    $unrelated = bin2hex( random_bytes( 32 ) );
    [ $client ] = self::clientAt( 1, [ self::continuIdBody( $unrelated ) ] );
    $this->expectException( WalletSignatureException::class );
    $this->expectExceptionMessage( "KnishIO: wallet {$unrelated} is not derived from this secret at position " . self::CLIENT_POSITION . ' under OTS version 1 or 2.' );
    $client->getSourceWallet();
  }

  public function testLoginPointerIsUsedAtTheVersionOfItsAddress (): void {
    [ $client ] = self::clientAt( 1, [ self::continuIdBody( self::addressAt( 2 ) ), self::proposeBody( true ) ] );
    $this->assertTrue( $client->requestProfileAuthToken( self::clientSecret(), false )->success() );
    $authWallet = $client->getAuthToken()->getWallet();
    $this->assertSame( 'USER', $authWallet->token );
    $this->assertSame( 2, $authWallet->otsVersion );
    $this->assertSame( self::addressAt( 2 ), $authWallet->address );
  }

  public function testAuthTokenSnapshotKeepsOtsVersion (): void {
    $wallet = new Wallet( self::clientSecret(), 'USER', self::CLIENT_POSITION, otsVersion: 2 );
    $snapshot = AuthToken::create( [ 'token' => 't', 'expiresAt' => '0', 'pubkey' => 'p' ], $wallet, false )->getSnapshot();
    $this->assertSame( 2, $snapshot[ 'wallet' ][ 'otsVersion' ] );
    $restored = AuthToken::restore( $snapshot, self::clientSecret() )->getWallet();
    $this->assertSame( 2, $restored->otsVersion );
    $this->assertSame( $wallet->address, $restored->address );

    unset( $snapshot[ 'wallet' ][ 'otsVersion' ] );
    $this->assertSame( 1, AuthToken::restore( $snapshot, self::clientSecret() )->getWallet()->otsVersion );
  }

  /**
   * Atoms of the molecule the client proposed in request $index
   *
   * @return array[]
   */
  private static function proposedAtoms ( RecordingHttpClient $http, int $index ): array {
    $request = json_decode( (string) $http->requests[ $index ]->getBody(), true, 512, JSON_THROW_ON_ERROR );
    return $request[ 'variables' ][ 'molecule' ][ 'atoms' ];
  }

  private static function continuIdAtom ( array $atoms ): array {
    $found = array_values( array_filter( $atoms, static fn ( array $atom ) => $atom[ 'isotope' ] === 'I' ) );
    return $found[ 0 ];
  }

  private static function walletData ( string $token, string $position, string $address, ?string $pubkey = null ): array {
    return [
      'type' => 'regular',
      'address' => $address,
      'bundleHash' => Crypto::generateBundleHash( self::clientSecret() ),
      'tokenSlug' => $token,
      'position' => $position,
      'batchId' => null,
      'characters' => 'BASE64',
      'pubkey' => $pubkey,
      'amount' => '100',
      'createdAt' => '2026-10-12T00:00:00Z',
      'tokenUnits' => [],
      'tradeRates' => [],
    ];
  }

  public function testV2ClientSigningInFromV1PointerRegistersV2Head (): void {
    [ $client, $http ] = self::clientAt( 2, [ self::continuIdBody( self::addressAt( 1 ) ), self::proposeBody( true ) ] );
    $this->assertTrue( $client->requestProfileAuthToken( self::clientSecret(), false )->success() );
    $this->assertCount( 2, $http->requests );
    $atoms = self::proposedAtoms( $http, 1 );
    $this->assertSame( self::addressAt( 1 ), $atoms[ 0 ][ 'walletAddress' ] );
    $head = self::continuIdAtom( $atoms );
    $this->assertSame( self::addressAt( 2, 'USER', $head[ 'position' ] ), $head[ 'walletAddress' ] );
    $this->assertSame( 1, $client->getAuthToken()->getWallet()->otsVersion );
  }

  public function testV2ClientSigningFromV1HeadRegistersV2Head (): void {
    [ $client, $http ] = self::clientAt( 2, [ self::continuIdBody( self::addressAt( 1 ) ), self::proposeBody( false ) ] );
    $client->createMeta( 'ots', 'migrate', [ 'k' => 'v' ] );
    $atoms = self::proposedAtoms( $http, 1 );
    $this->assertSame( self::addressAt( 1 ), $atoms[ 0 ][ 'walletAddress' ] );
    $head = self::continuIdAtom( $atoms );
    $this->assertSame( self::addressAt( 2, 'USER', $head[ 'position' ] ), $head[ 'walletAddress' ] );
  }

  public function testV1ClientSigningFromV2HeadKeepsV2Head (): void {
    [ $client, $http ] = self::clientAt( 1, [ self::continuIdBody( self::addressAt( 2 ) ), self::proposeBody( false ) ] );
    $client->createMeta( 'ots', 'keep', [ 'k' => 'v' ] );
    $atoms = self::proposedAtoms( $http, 1 );
    $this->assertSame( self::addressAt( 2 ), $atoms[ 0 ][ 'walletAddress' ] );
    $head = self::continuIdAtom( $atoms );
    $this->assertSame( self::addressAt( 2, 'USER', $head[ 'position' ] ), $head[ 'walletAddress' ] );
  }

  public function testCreateRemainderTakesTheHigherOfWalletAndMinimumOtsVersion (): void {
    $secret = self::clientSecret();
    $v1 = new Wallet( $secret, 'TKN', self::CLIENT_POSITION );
    $v2 = new Wallet( $secret, 'TKN', self::CLIENT_POSITION, otsVersion: 2 );
    $this->assertSame( 1, $v1->createRemainder( $secret )->otsVersion );
    $this->assertSame( 2, $v1->createRemainder( $secret, 2 )->otsVersion );
    $this->assertSame( 2, $v2->createRemainder( $secret, 1 )->otsVersion );
    $this->expectException( CryptoException::class );
    $this->expectExceptionMessage( 'KnishIO: unsupported OTS version 3; expected 1 or 2.' );
    $v1->createRemainder( $secret, 3 );
  }

  public function testMoleculeCreatesSignerWalletsAtTheHigherOfSigningAndMinimumOtsVersion (): void {
    $secret = self::clientSecret();
    $sourceWallet = new Wallet( $secret, 'TKN', self::CLIENT_POSITION );
    $sourceWallet->balance = 10;
    $molecule = new Molecule( $secret, $sourceWallet, minOtsVersion: 2 );
    $this->assertSame( 1, $molecule->signingOtsVersion() );
    $this->assertSame( 2, $molecule->successorOtsVersion() );
    $this->assertSame( 2, $molecule->remainderWallet()->otsVersion );

    $molecule->initDepositBuffer( 4, [] );
    $buffers = array_values( array_filter( $molecule->atoms, static fn ( Atom $atom ) => $atom->isotope === 'B' ) );
    $this->assertSame( self::addressAt( 2, 'TKN', $buffers[ 0 ]->position ), $buffers[ 0 ]->walletAddress );

    $molecule->addContinuIdAtom();
    $this->assertSame( 'USER', $molecule->remainderWallet()->token );
    $this->assertSame( 2, $molecule->remainderWallet()->otsVersion );
    $this->assertSame( 1, ( new Molecule( $secret, $sourceWallet ) )->successorOtsVersion() );
  }

  public function testV2ClientTransferringFromV1TokenWalletCreatesV2Remainder (): void {
    $tokenPosition = str_repeat( 'd', 64 );
    $sourceAddress = self::addressAt( 1, 'TKN', $tokenPosition );
    $balanceBody = json_encode( [ 'data' => [ 'Balance' => self::walletData( 'TKN', $tokenPosition, $sourceAddress ) ] ], JSON_THROW_ON_ERROR );
    [ $client, $http ] = self::clientAt( 2, [ $balanceBody, self::proposeBody( false ) ] );
    $client->transferToken( Crypto::generateBundleHash( str_repeat( 'e', 2048 ) ), 'TKN', 10 );
    $atoms = self::proposedAtoms( $http, 1 );
    $this->assertSame( $sourceAddress, $atoms[ 0 ][ 'walletAddress' ] );
    $remainders = array_values( array_filter( $atoms, static fn ( array $atom, int $index ) => $index > 0 && $atom[ 'isotope' ] === 'V' && $atom[ 'token' ] === 'TKN' && $atom[ 'position' ], ARRAY_FILTER_USE_BOTH ) );
    $this->assertCount( 1, $remainders );
    $this->assertSame( self::addressAt( 2, 'TKN', $remainders[ 0 ][ 'position' ] ), $remainders[ 0 ][ 'walletAddress' ] );
  }

  public function testLedgerWalletRebuiltWithSecretIsKeyedAtTheVersionOfItsAddress (): void {
    $secret = self::clientSecret();
    $tokenPosition = str_repeat( 'd', 64 );
    $expected = new Wallet( $secret, 'TKN', $tokenPosition, otsVersion: 2 );
    $mlkemPublicKey = new \ReflectionProperty( Wallet::class, 'mlkemPublicKey' );

    $rebuilt = ResponseWalletList::toClientWallet( self::walletData( 'TKN', $tokenPosition, self::addressAt( 2, 'TKN', $tokenPosition ) ), $secret );
    $this->assertSame( 2, $rebuilt->otsVersion );
    $this->assertSame( $expected->key, $rebuilt->key );
    $this->assertSame( $expected->address, $rebuilt->address );
    $this->assertSame( $mlkemPublicKey->getValue( $expected ), $mlkemPublicKey->getValue( $rebuilt ) );

    $unrelated = bin2hex( random_bytes( 32 ) );
    $keyless = ResponseWalletList::toClientWallet( self::walletData( 'TKN', $tokenPosition, $unrelated ), $secret );
    $this->assertNull( $keyless->key );
    $this->assertSame( $unrelated, $keyless->address );
  }
}
