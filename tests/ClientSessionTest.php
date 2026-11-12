<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use ReflectionProperty;
use WishKnish\KnishIO\Client\AuthToken;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Storage\MemorySecretStorageProvider;
use WishKnish\KnishIO\Client\Wallet;

/**
 * AuthToken expiry as JS AuthToken.getExpireInterval() computes it, and a client whose secret lives
 * only in a SecretStorageProvider (setSecretStorage(), no setSecret()).
 */
class ClientSessionTest extends TestCase {

  private const POINTER = 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210';

  private static function secret (): string {
    return Crypto::generateSecret( 'php-client-session-test', 2048 );
  }

  private static function token ( int $expiresAt ): AuthToken {
    return new AuthToken( 'token', (string) $expiresAt, 'pubkey', false );
  }

  // getExpireInterval() returns milliseconds until expiry

  public function testFutureExpiryIsAPositiveInterval (): void {
    $token = self::token( time() + 3600 );
    $interval = $token->getExpireInterval();
    $this->assertIsInt( $interval );
    $this->assertGreaterThan( 3500 * 1000, $interval );
    $this->assertLessThanOrEqual( 3600 * 1000, $interval );
    $this->assertFalse( $token->isExpired() );
  }

  public function testPastExpiryIsANegativeInterval (): void {
    $token = self::token( time() - 60 );
    $this->assertLessThan( 0, $token->getExpireInterval() );
    $this->assertTrue( $token->isExpired() );
  }

  // A client with only secret storage configured can build molecules

  private static function storageOnlyClient ( RecordingHttpClient $http ): KnishIOClient {
    $secret = self::secret();
    $bundle = Crypto::generateBundleHash( $secret );
    $storage = new MemorySecretStorageProvider();
    $storage->storeSecret( $bundle, $secret );
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http );
    $client->setSecretStorage( $storage, $bundle );
    return $client;
  }

  private static function continuIdBody (): string {
    $secret = self::secret();
    return json_encode( [ 'data' => [ 'ContinuId' => [
      'type' => 'regular',
      'address' => ( new Wallet( $secret, 'USER', self::POINTER ) )->address,
      'bundleHash' => Crypto::generateBundleHash( $secret ),
      'tokenSlug' => 'USER',
      'position' => self::POINTER,
      'batchId' => null,
      'characters' => 'BASE64',
      'pubkey' => null,
      'amount' => '0',
      'createdAt' => '2026-09-24T00:00:00Z',
    ] ] ], JSON_THROW_ON_ERROR );
  }

  private static function cleartextSecret ( KnishIOClient $client ): ?string {
    return ( new ReflectionProperty( KnishIOClient::class, 'secret' ) )->getValue( $client );
  }

  public function testGetSecretResolvesThroughStorageWithoutCachingIt (): void {
    $client = self::storageOnlyClient( new RecordingHttpClient( 'http://offline.invalid/graphql', [] ) );
    $this->assertSame( self::secret(), $client->getSecret() );
    $this->assertNull( self::cleartextSecret( $client ) );
  }

  public function testCreateMoleculeWorksWithStorageOnly (): void {
    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [ self::continuIdBody() ] );
    $client = self::storageOnlyClient( $http );

    $molecule = $client->createMolecule();

    $this->assertSame( self::POINTER, $molecule->sourceWallet()->position );
    $this->assertSame( self::secret(), $molecule->secret() );
    $this->assertNull( self::cleartextSecret( $client ) );
  }

  public function testRequestAuthTokenUsesTheStoredSecret (): void {
    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      self::continuIdBody(),
      json_encode( [ 'data' => [ 'ProposeMolecule' => [
        'molecularHash' => str_repeat( 'a', 64 ), 'height' => 0, 'depth' => 0, 'status' => 'accepted', 'reason' => null,
        'payload' => json_encode( [ 'token' => 'offline-auth-token', 'expiresAt' => (string) ( time() + 3600 ), 'pubkey' => 'offline-validator-pubkey', 'encrypt' => false ], JSON_THROW_ON_ERROR ),
        'createdAt' => '2026-09-24T00:00:00Z',
      ] ] ], JSON_THROW_ON_ERROR ),
    ] );
    $client = self::storageOnlyClient( $http );

    $this->assertTrue( $client->requestAuthToken( null )->success() );

    $query = json_decode( (string) $http->requests[ 0 ]->getBody(), true, 512, JSON_THROW_ON_ERROR );
    $this->assertStringContainsString( 'ContinuId(', $query[ 'query' ], 'a profile login, signed from the stored secret, not a guest login' );
    $this->assertSame( 'offline-auth-token', $client->getAuthToken()?->getToken() );
    // Like JS requestProfileAuthToken(), the login itself then calls setSecret()
    $this->assertSame( self::secret(), $client->getSecret() );
  }
}
