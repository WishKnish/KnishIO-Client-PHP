<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;

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
 * Validator 0.5.0 (f120a65) no longer executes the I-atom of an unproven re-authorization, so
 * the auth molecule's USER remainder wallet is never registered and the ContinuID pointer does
 * not move. After a profile authorization the next molecule must therefore resolve its source
 * wallet through the ContinuID query, not adopt the cached auth remainder.
 */
class AuthContinuIdSourceWalletTest extends TestCase {

  private const CONTINUID_POSITION = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

  public function testMoleculeAfterProfileAuthResolvesSourceWalletViaContinuId (): void {
    $secret = Crypto::generateSecret( 'auth-continuid-source-wallet-test-seed', 2048 );
    $bundle = Crypto::generateBundleHash( $secret );

    $authBody = json_encode( [ 'data' => [ 'ProposeMolecule' => [
      'molecularHash' => str_repeat( 'a', 64 ),
      'height' => 0,
      'depth' => 0,
      'status' => 'accepted',
      'reason' => null,
      'payload' => json_encode( [
        'token' => 'offline-auth-token',
        'expiresAt' => (string) ( time() + 3600 ),
        'pubkey' => 'offline-validator-pubkey',
        'encrypt' => false,
      ], JSON_THROW_ON_ERROR ),
      'createdAt' => '2026-09-24T00:00:00Z',
    ] ] ], JSON_THROW_ON_ERROR );

    $continuIdBody = json_encode( [ 'data' => [ 'ContinuId' => [
      'type' => 'regular',
      'address' => str_repeat( 'b', 64 ),
      'bundleHash' => $bundle,
      'tokenSlug' => 'USER',
      'position' => self::CONTINUID_POSITION,
      'batchId' => null,
      'characters' => 'BASE64',
      'pubkey' => null,
      'amount' => '0',
      'createdAt' => '2026-09-24T00:00:00Z',
    ] ] ], JSON_THROW_ON_ERROR );

    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [ $authBody, $continuIdBody ] );
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http );

    $authResponse = $client->requestProfileAuthToken( $secret, false );
    $this->assertTrue( $authResponse->success(), 'stubbed auth response must be accepted' );
    $this->assertSame( 'USER', $client->getRemainderWallet()?->token, 'auth must leave a cached USER remainder' );

    $molecule = $client->createMolecule();

    $this->assertCount( 2, $http->requests, 'createMolecule after profile auth must issue a ContinuId query' );
    $this->assertStringContainsString( 'ContinuId(', (string) $http->requests[ 1 ]->getBody() );
    $this->assertSame( self::CONTINUID_POSITION, $molecule->sourceWallet()->position,
      'source wallet must be the ContinuID pointer, not the unregistered auth remainder' );
  }
}
