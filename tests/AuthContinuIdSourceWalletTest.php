<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WishKnish\KnishIO\Client\AuthToken;
use WishKnish\KnishIO\Client\Exception\TokenTypeException;
use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Offline HTTP client: records every request and answers from a queue of canned GraphQL bodies.
 * It extends HttpClient because KnishIOClient stores its client in a `HttpClient`-typed property.
 */
final class RecordingHttpClient extends HttpClient {

  /** @var RequestInterface[] */
  public array $requests = [];

  /** @var string[] */
  private array $bodies;

  public function __construct ( string $uri, array $bodies ) {
    parent::__construct( $uri );
    $this->bodies = $bodies;
  }

  public function send ( RequestInterface $request, array $options = [] ): ResponseInterface {
    $this->requests[] = $request;
    if ( !$this->bodies ) {
      throw new \RuntimeException( 'RecordingHttpClient: unexpected request #' . count( $this->requests ) . ': ' . (string) $request->getBody() );
    }
    return new Response( 200, [ 'Content-Type' => 'application/json' ], array_shift( $this->bodies ) );
  }
}

/**
 * Validator 0.5.0 (f120a65) marks a re-authorization proven only when it is signed from the
 * identity's ContinuID pointer by the USER wallet registered there; only then does it execute the
 * I-atom and move the pointer. A profile login therefore signs from the pointer when one exists,
 * falls back once to a fresh AUTH wallet if that is rejected, and the next molecule resolves its
 * source wallet through the ContinuID query rather than the cached auth remainder.
 */
class AuthContinuIdSourceWalletTest extends TestCase {

  private const CONTINUID_POSITION = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

  private const SEED = 'auth-continuid-source-wallet-test-seed';

  private static function authBody ( bool $accepted, ?string $reason = null ): string {
    return json_encode( [ 'data' => [ 'ProposeMolecule' => [
      'molecularHash' => str_repeat( 'a', 64 ),
      'height' => 0,
      'depth' => 0,
      'status' => $accepted ? 'accepted' : 'rejected',
      'reason' => $reason,
      'payload' => $accepted ? json_encode( [
        'token' => 'offline-auth-token',
        'expiresAt' => (string) ( time() + 3600 ),
        'pubkey' => 'offline-validator-pubkey',
        'encrypt' => false,
      ], JSON_THROW_ON_ERROR ) : null,
      'createdAt' => '2026-09-24T00:00:00Z',
    ] ] ], JSON_THROW_ON_ERROR );
  }

  private static function continuIdBody ( ?string $bundle, string $tokenSlug = 'USER', ?string $address = null ): string {
    return json_encode( [ 'data' => [ 'ContinuId' => $bundle === null ? null : [
      'type' => 'regular',
      'address' => $address,
      'bundleHash' => $bundle,
      'tokenSlug' => $tokenSlug,
      'position' => self::CONTINUID_POSITION,
      'batchId' => null,
      'characters' => 'BASE64',
      'pubkey' => null,
      'amount' => '0',
      'createdAt' => '2026-09-24T00:00:00Z',
    ] ] ], JSON_THROW_ON_ERROR );
  }

  /**
   * @return array<string, mixed>
   */
  private static function requestJson ( RequestInterface $request ): array {
    return json_decode( (string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR );
  }

  /**
   * @param RequestInterface[] $requests
   *
   * @return array<int, array<int, array<string, mixed>>> atoms of every ProposeMolecule request, in order
   */
  private static function proposals ( array $requests ): array {
    $proposals = [];
    foreach ( $requests as $request ) {
      $json = self::requestJson( $request );
      if ( str_contains( $json[ 'query' ], 'ProposeMolecule(' ) ) {
        $proposals[] = $json[ 'variables' ][ 'molecule' ][ 'atoms' ];
      }
    }
    return $proposals;
  }

  /**
   * @param array<string, mixed> $atom
   */
  private static function metaValue ( array $atom, string $key ): ?string {
    foreach ( $atom[ 'meta' ] as $meta ) {
      if ( $meta[ 'key' ] === $key ) {
        return $meta[ 'value' ];
      }
    }
    return null;
  }

  private static function secret (): string {
    return Crypto::generateSecret( self::SEED, 2048 );
  }

  public function testReturningIdentitySignsAuthorizationFromContinuIdPointer (): void {
    $secret = self::secret();
    $bundle = Crypto::generateBundleHash( $secret );
    $pointerAddress = ( new Wallet( $secret, 'USER', self::CONTINUID_POSITION ) )->address;

    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      self::continuIdBody( $bundle, 'USER', $pointerAddress ),
      self::authBody( true ),
    ] );
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http );

    $response = $client->requestProfileAuthToken( $secret, false );
    $this->assertTrue( $response->success() );

    $query = self::requestJson( $http->requests[ 0 ] );
    $this->assertStringContainsString( 'ContinuId(', $query[ 'query' ] );
    $this->assertSame( [ 'bundle' => $bundle, 'token' => 'USER' ], $query[ 'variables' ], 'the pointer must be queried with token USER' );

    $proposals = self::proposals( $http->requests );
    $this->assertCount( 1, $proposals, 'an accepted pointer-signed login sends exactly one authorization' );
    [ $u, $i ] = $proposals[ 0 ];
    $this->assertSame( 'U', $u[ 'isotope' ] );
    $this->assertSame( 'USER', $u[ 'token' ] );
    $this->assertSame( self::CONTINUID_POSITION, $u[ 'position' ] );
    $this->assertSame( $pointerAddress, $u[ 'walletAddress' ] );
    $this->assertSame( 'I', $i[ 'isotope' ] );
    $this->assertSame( self::CONTINUID_POSITION, self::metaValue( $i, 'previousPosition' ) );
    $this->assertNotSame( self::CONTINUID_POSITION, $i[ 'position' ], 'the I atom must name a fresh USER remainder position' );

    $authWallet = $client->getAuthToken()?->getWallet();
    $this->assertSame( 'USER', $authWallet?->token, 'the token must be bound to the pointer wallet' );
    $this->assertSame( self::CONTINUID_POSITION, $authWallet?->position );
    $this->assertSame( $authWallet?->pubkey, self::metaValue( $u, 'walletPubkey' ) );
  }

  /**
   * @return array<string, array{0: bool, 1: string}>
   */
  public static function noUsablePointerProvider (): array {
    return [
      'no ContinuID wallet' => [ false, 'USER' ],
      'non-USER wallet returned' => [ true, 'AUTH' ],
    ];
  }

  #[DataProvider( 'noUsablePointerProvider' )]
  public function testNoUsablePointerSignsWithFreshAuthWallet ( bool $hasWallet, string $tokenSlug ): void {
    $secret = self::secret();
    $bundle = Crypto::generateBundleHash( $secret );

    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      self::continuIdBody( $hasWallet ? $bundle : null, $tokenSlug ),
      self::authBody( true ),
    ] );
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http );

    $this->assertTrue( $client->requestProfileAuthToken( $secret, false )->success() );

    $proposals = self::proposals( $http->requests );
    $this->assertCount( 1, $proposals );
    $this->assertSame( 'AUTH', $proposals[ 0 ][ 0 ][ 'token' ] );
    $this->assertNotSame( self::CONTINUID_POSITION, $proposals[ 0 ][ 0 ][ 'position' ] );
    $this->assertSame( 'AUTH', $client->getAuthToken()?->getWallet()->token );
  }

  public function testPointerAddressMismatchSignsWithFreshAuthWallet (): void {
    $secret = self::secret();
    $bundle = Crypto::generateBundleHash( $secret );

    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      self::continuIdBody( $bundle, 'USER', str_repeat( 'b', 64 ) ),
      self::authBody( true ),
    ] );
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http );

    $this->assertTrue( $client->requestProfileAuthToken( $secret, false )->success() );

    $proposals = self::proposals( $http->requests );
    $this->assertCount( 1, $proposals );
    $this->assertSame( 'AUTH', $proposals[ 0 ][ 0 ][ 'token' ] );
  }

  public function testRejectedPointerLoginFallsBackOnceToAuthWallet (): void {
    $secret = self::secret();
    $bundle = Crypto::generateBundleHash( $secret );

    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      self::continuIdBody( $bundle ),
      self::authBody( false, 'pointer rejected' ),
      self::authBody( true ),
    ] );
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http );

    $this->assertTrue( $client->requestProfileAuthToken( $secret, false )->success() );

    $proposals = self::proposals( $http->requests );
    $this->assertCount( 2, $proposals, 'a rejected pointer login falls back exactly once' );
    $this->assertSame( 'USER', $proposals[ 0 ][ 0 ][ 'token' ] );
    $this->assertSame( 'AUTH', $proposals[ 1 ][ 0 ][ 'token' ] );
    $this->assertSame( 'AUTH', $client->getAuthToken()?->getWallet()->token );
  }

  public function testRejectedFallbackIsReturnedAsRejected (): void {
    $secret = self::secret();
    $bundle = Crypto::generateBundleHash( $secret );

    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      self::continuIdBody( $bundle ),
      self::authBody( false, 'pointer rejected' ),
      self::authBody( false, 'fallback rejected' ),
    ] );
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http );

    // PHP reports a rejected authorization as a non-success response (no exception), as before.
    $response = $client->requestProfileAuthToken( $secret, false );
    $this->assertFalse( $response->success() );
    $this->assertSame( 'fallback rejected', $response->reason() );
    $this->assertCount( 2, self::proposals( $http->requests ), 'no third authorization after a rejected fallback' );
    $this->assertNull( $http->getAuthToken(), 'a rejected login must not install an auth token' );
  }

  public function testUIsotopeAcceptsAuthAndUserTokensOnly (): void {
    $secret = self::secret();

    $userSigned = new Molecule( $secret, new Wallet( $secret, 'USER', self::CONTINUID_POSITION ) );
    $userSigned->initAuthorization( [ 'encrypt' => 'false' ] );
    $userSigned->sign();
    $userSigned->check();

    $otherSigned = new Molecule( $secret, new Wallet( $secret, 'TEST' ) );
    $otherSigned->initAuthorization( [ 'encrypt' => 'false' ] );
    $otherSigned->sign();
    $this->expectException( TokenTypeException::class );
    $otherSigned->check();
  }

  public function testAuthTokenSnapshotRestoresBoundWalletToken (): void {
    $secret = self::secret();
    $payload = [ 'token' => 'jwt', 'expiresAt' => '99999999999', 'pubkey' => 'validator-pubkey' ];

    $userWallet = new Wallet( $secret, 'USER', self::CONTINUID_POSITION );
    $snapshot = AuthToken::create( $payload, $userWallet, false )->getSnapshot();
    $this->assertSame( 'USER', $snapshot[ 'wallet' ][ 'token' ] );
    $restored = AuthToken::restore( $snapshot, $secret )->getWallet();
    $this->assertSame( 'USER', $restored->token );
    $this->assertSame( $userWallet->address, $restored->address );
    $this->assertSame( $userWallet->pubkey, $restored->pubkey );

    $authWallet = new Wallet( $secret, 'AUTH', self::CONTINUID_POSITION );
    $legacy = AuthToken::create( $payload, $authWallet, false )->getSnapshot();
    unset( $legacy[ 'wallet' ][ 'token' ] );
    $restoredLegacy = AuthToken::restore( $legacy, $secret )->getWallet();
    $this->assertSame( 'AUTH', $restoredLegacy->token, 'a snapshot without wallet.token restores an AUTH wallet' );
    $this->assertSame( $authWallet->address, $restoredLegacy->address );
  }

  public function testMoleculeAfterProfileAuthResolvesSourceWalletViaContinuId (): void {
    $secret = self::secret();
    $bundle = Crypto::generateBundleHash( $secret );

    // First login of this identity: no ContinuID pointer yet, so the auth signs from a fresh AUTH wallet.
    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      self::continuIdBody( null ),
      self::authBody( true ),
      self::continuIdBody( $bundle, 'USER', str_repeat( 'b', 64 ) ),
    ] );
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http );

    $authResponse = $client->requestProfileAuthToken( $secret, false );
    $this->assertTrue( $authResponse->success(), 'stubbed auth response must be accepted' );
    $this->assertSame( 'USER', $client->getRemainderWallet()?->token, 'auth must leave a cached USER remainder' );

    $molecule = $client->createMolecule();

    $this->assertCount( 3, $http->requests, 'createMolecule after profile auth must issue a ContinuId query' );
    $this->assertStringContainsString( 'ContinuId(', (string) $http->requests[ 2 ]->getBody() );
    $this->assertSame( self::CONTINUID_POSITION, $molecule->sourceWallet()->position,
      'source wallet must be the ContinuID pointer, not the unregistered auth remainder' );
  }
}
