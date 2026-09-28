<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Exception\WalletShadowException;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Wallet;

/**
 * claimShadowWallet() without a batch ID claims the first SHADOW wallet queryWallets() lists for
 * the token, as the JS SDK does; the validator rejects a claim without one ("Shadow wallet claim
 * requires batch_id").
 */
class ClaimShadowWalletTest extends TestCase {

  private const TOKEN = 'CLAIMTOK';

  private const POINTER = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

  private static function secret (): string {
    return Crypto::generateSecret( 'php-claim-shadow-wallet-test' );
  }

  /**
   * @param array<string, mixed> $overrides
   *
   * @return array<string, mixed>
   */
  private static function walletRow ( array $overrides ): array {
    return array_merge( [
      'type' => 'regular',
      'address' => null,
      'bundleHash' => Crypto::generateBundleHash( self::secret() ),
      'token' => null,
      'tokenUnits' => [],
      'tradeRates' => [],
      'tokenSlug' => self::TOKEN,
      'batchId' => null,
      'position' => null,
      'amount' => '10',
      'characters' => 'BASE64',
      'pubkey' => null,
      'createdAt' => '2026-09-28T00:00:00Z',
    ], $overrides );
  }

  private static function walletListBody ( array $rows ): string {
    return json_encode( [ 'data' => [ 'Wallet' => $rows ] ], JSON_THROW_ON_ERROR );
  }

  private static function continuIdBody (): string {
    return json_encode( [ 'data' => [ 'ContinuId' => self::walletRow( [
      'tokenSlug' => 'USER',
      'position' => self::POINTER,
      'address' => ( new Wallet( self::secret(), 'USER', self::POINTER ) )->address,
      'amount' => '0',
    ] ) ] ], JSON_THROW_ON_ERROR );
  }

  private static function proposeBody (): string {
    return json_encode( [ 'data' => [ 'ProposeMolecule' => [
      'molecularHash' => str_repeat( 'a', 64 ), 'height' => 0, 'depth' => 0, 'status' => 'accepted',
      'reason' => null, 'payload' => null, 'createdAt' => '2026-09-28T00:00:00Z',
    ] ] ], JSON_THROW_ON_ERROR );
  }

  private static function client ( RecordingHttpClient $http ): KnishIOClient {
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http );
    $client->setSecret( self::secret() );
    return $client;
  }

  /**
   * @return array<string, mixed>
   */
  private static function body ( RecordingHttpClient $http, int $index ): array {
    return json_decode( (string) $http->requests[ $index ]->getBody(), true, 512, JSON_THROW_ON_ERROR );
  }

  public function testOmittedBatchIdClaimsTheShadowWalletListedAfterARegularOne (): void {
    $regular = self::walletRow( [
      'position' => str_repeat( 'c', 64 ),
      'address' => str_repeat( 'd', 64 ),
      'batchId' => 'batch-regular',
    ] );
    $shadow = self::walletRow( [ 'batchId' => 'batch-shadow-1' ] );
    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      self::walletListBody( [ $regular, $shadow ] ),
      self::continuIdBody(),
      self::proposeBody(),
    ] );

    $this->assertTrue( self::client( $http )->claimShadowWallet( self::TOKEN )->success() );

    $this->assertCount( 3, $http->requests );
    $walletQuery = self::body( $http, 0 );
    $this->assertStringContainsString( 'Wallet(', $walletQuery[ 'query' ] );
    $this->assertSame( self::TOKEN, $walletQuery[ 'variables' ][ 'tokenSlug' ] );

    $claimAtom = self::body( $http, 2 )[ 'variables' ][ 'molecule' ][ 'atoms' ][ 0 ];
    $this->assertSame( 'C', $claimAtom[ 'isotope' ] );
    $this->assertSame( 'batch-shadow-1', $claimAtom[ 'batchId' ], 'the shadow wallet\'s batch id, not the regular wallet\'s' );
  }

  public function testOmittedBatchIdWithoutAShadowWalletIsRefusedBeforeAnyMolecule (): void {
    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      self::walletListBody( [ self::walletRow( [ 'position' => str_repeat( 'c', 64 ), 'address' => str_repeat( 'd', 64 ) ] ) ] ),
    ] );

    try {
      self::client( $http )->claimShadowWallet( self::TOKEN );
      $this->fail( 'a claim without a shadow wallet must be refused' );
    }
    catch ( WalletShadowException $e ) {
      $this->assertStringContainsString( 'No shadow wallets found for token ' . self::TOKEN, $e->getMessage() );
    }
    $this->assertCount( 1, $http->requests, 'only the wallet query is sent' );
  }

  public function testExplicitBatchIdSkipsTheWalletQuery (): void {
    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      self::continuIdBody(),
      self::proposeBody(),
    ] );

    self::client( $http )->claimShadowWallet( self::TOKEN, 'batch-explicit' );

    $this->assertStringContainsString( 'ContinuId(', self::body( $http, 0 )[ 'query' ] );
    $this->assertSame( 'batch-explicit', self::body( $http, 1 )[ 'variables' ][ 'molecule' ][ 'atoms' ][ 0 ][ 'batchId' ] );
  }
}
