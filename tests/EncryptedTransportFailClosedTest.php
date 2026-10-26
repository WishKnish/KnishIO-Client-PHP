<?php
/*
 * Offline fail-closed checks for the encrypted (CipherHash) transport middleware.
 * With encryption enabled, a normal operation must never leave the client in
 * plaintext when the authorized wallet or the validator public key is missing.
 */

namespace WishKnish\KnishIO\Client\Tests;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use WishKnish\KnishIO\Client\Exception\CodeException;
use WishKnish\KnishIO\Client\Libraries\Cipher;
use WishKnish\KnishIO\Client\Wallet;

class EncryptedTransportFailClosedTest extends TestCase {

  private const NORMAL_QUERY = 'query { Balance(token: "USER") { address } }';
  private const SCHEMA_QUERY = 'query { __schema { types { name } } }';

  /**
   * Builds a Guzzle client whose stack carries the real Cipher middleware over a mock handler.
   *
   * @return array{0: Client, 1: MockHandler}
   */
  private static function client ( ?Wallet $wallet, ?string $pubkey ): array {
    $mock = new MockHandler( [ new Response( 200, [], '{"data":{}}' ) ] );
    $stack = new HandlerStack( $mock );
    $cipher = new Cipher( $wallet, $pubkey );
    $handler = Closure::bind( fn () => $this->handler(), $cipher, Cipher::class )();
    $stack->push( $handler );
    return [ new Client( [ 'handler' => $stack ] ), $mock ];
  }

  private static function send ( Client $client, string $query ): \Psr\Http\Message\ResponseInterface {
    return $client->post( 'http://offline.invalid/graphql', [
      'encrypt' => true,
      'body' => json_encode( [ 'query' => $query, 'variables' => [] ], JSON_THROW_ON_ERROR ),
      'headers' => [ 'Content-Type' => 'application/json' ],
    ] );
  }

  private static function wallet (): Wallet {
    return new Wallet( str_repeat( 'a1b2c3d4e5f6', 8 ), 'AUTH' );
  }

  private function assertRefused ( ?Wallet $wallet, ?string $pubkey, string $message ): void {
    [ $client, $mock ] = self::client( $wallet, $pubkey );
    try {
      self::send( $client, self::NORMAL_QUERY );
      $this->fail( 'Encrypted transport sent a request without ' . $message );
    }
    catch ( CodeException $e ) {
      $this->assertSame( $message, $e->getMessage() );
    }
    // The single queued response is still unconsumed: nothing reached the wire.
    $this->assertSame( 1, $mock->count(), 'no request may be sent' );
  }

  public function testRefusesToSendNormalOperationWithoutWallet (): void {
    $this->assertRefused( null, null, 'Authorized wallet missing.' );
  }

  public function testRefusesWhenValidatorPubkeyIsMissing (): void {
    $this->assertRefused( self::wallet(), null, 'Server public key missing.' );
  }

  public function testRefusesWhenValidatorPubkeyIsEmpty (): void {
    $this->assertRefused( self::wallet(), '', 'Server public key missing.' );
  }

  public function testStillSendsBypassedOperationInPlaintext (): void {
    [ $client, $mock ] = self::client( null, null );
    self::send( $client, self::SCHEMA_QUERY );
    $this->assertSame( 0, $mock->count(), 'exactly one request must be sent' );
    $sent = (string) $mock->getLastRequest()->getBody();
    $this->assertStringContainsString( '__schema', $sent );
    $this->assertStringNotContainsString( 'CipherHash', $sent );
  }

}
