<?php

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Libraries\Crypto\SHA3\Sponge;
use WishKnish\KnishIO\Client\Libraries\NobleMLKEMBridge;
use WishKnish\KnishIO\Client\Wallet;

/**
 * The persistent Node ML-KEM helper: one process serves every call, and a helper that dies is
 * replaced on the next call. Called directly, so it runs whether or not libkcore is loaded.
 */
class NobleMLKEMBridgeTest extends TestCase {

  protected function setUp (): void {
    if ( !NobleMLKEMBridge::isAvailable() ) {
      $this->markTestSkipped( 'Node.js or @noble/post-quantum is not installed' );
    }
    NobleMLKEMBridge::stopHelper();
  }

  protected function tearDown (): void {
    NobleMLKEMBridge::overrideHelperForTests( null );
  }

  public function testAWedgedHelperTimesOutAndIsKilledWithoutBlocking (): void {
    if ( PHP_OS_FAMILY === 'Windows' ) {
      $this->markTestSkipped( 'Windows cannot select() on proc_open pipes; the helper read there has no deadline' );
    }
    NobleMLKEMBridge::overrideHelperForTests( __DIR__ . '/fixtures/wedged-mlkem-helper.mjs', 0.5 );
    $start = microtime( true );
    try {
      NobleMLKEMBridge::encapsulate( base64_encode( str_repeat( "\0", 1568 ) ) );
      $this->fail( 'encapsulate() returned from a helper that never answers' );
    }
    catch ( \Exception $e ) {
      $this->assertSame( 'Noble ML-KEM helper did not answer within 0.5 s', $e->getMessage() );
    }
    // 0.5 s answer timeout + 0.5 s exit grace, then the kill: well under the 30 s default.
    $this->assertLessThan( 3.0, microtime( true ) - $start );
    $this->assertNull( NobleMLKEMBridge::helperPid() );
  }

  /** @return array{publicKey: string, secretKey: string, expectedPubkey: string} */
  private function keypair1024 (): array {
    $path = __DIR__ . '/fixtures/cross-platform-test-vectors.json';
    $kg = json_decode( file_get_contents( $path ), true )[ 'vectors' ][ 'mlkem1024' ][ 'keygen' ];
    $key = Wallet::generateKey( $kg[ 'secret' ], $kg[ 'token' ], $kg[ 'position' ] );
    $seed = bin2hex( Sponge::init( Sponge::SHAKE256 )->absorb( $key )->squeeze( 64 ) );
    return NobleMLKEMBridge::generateMLKEMKeyPairFromSeed( $seed, 1024 ) + [ 'expectedPubkey' => $kg[ 'expectedPubkey' ] ];
  }

  public function testOneHelperServesEveryCall (): void {
    $pair = $this->keypair1024();
    $this->assertSame( $pair[ 'expectedPubkey' ], $pair[ 'publicKey' ], 'keygen KAT' );
    $pid = NobleMLKEMBridge::helperPid();
    $this->assertNotNull( $pid );

    $first = NobleMLKEMBridge::encapsulate( $pair[ 'publicKey' ] );
    $second = NobleMLKEMBridge::encapsulate( $pair[ 'publicKey' ] );
    $this->assertSame( $pid, NobleMLKEMBridge::helperPid() );
    $this->assertNotSame( $first[ 'ciphertext' ], $second[ 'ciphertext' ] );
    $this->assertSame( $second[ 'sharedSecret' ], NobleMLKEMBridge::decapsulate( $second[ 'ciphertext' ], $pair[ 'secretKey' ] )[ 'sharedSecret' ] );
  }

  // Up to 1.6.1 the seed and the secret key were node command-line arguments, readable through ps.
  public function testNoKeyMaterialOnTheHelperCommandLine (): void {
    if ( PHP_OS_FAMILY === 'Windows' ) {
      $this->markTestSkipped( 'reads the command line with ps' );
    }
    $pair = $this->keypair1024();
    $enc = NobleMLKEMBridge::encapsulate( $pair[ 'publicKey' ] );
    NobleMLKEMBridge::decapsulate( $enc[ 'ciphertext' ], $pair[ 'secretKey' ] );

    $args = trim( (string) shell_exec( 'ps -o args= -p ' . NobleMLKEMBridge::helperPid() ) );
    $this->assertStringEndsWith( 'noble-mlkem-bridge.js serve', $args );
    foreach ( [ $pair[ 'secretKey' ], $pair[ 'publicKey' ], $enc[ 'ciphertext' ] ] as $secret ) {
      $this->assertStringNotContainsString( substr( $secret, 0, 32 ), $args );
    }
  }

  public function testAKilledHelperIsReplacedOnTheNextCall (): void {
    if ( !function_exists( 'posix_kill' ) ) {
      $this->markTestSkipped( 'posix_kill is unavailable' );
    }
    $pair = $this->keypair1024();
    $pid = NobleMLKEMBridge::helperPid();
    posix_kill( $pid, 9 );
    usleep( 100000 );

    $result = NobleMLKEMBridge::encapsulate( $pair[ 'publicKey' ] );
    $this->assertNotSame( $pid, NobleMLKEMBridge::helperPid() );
    $this->assertSame( $result[ 'sharedSecret' ], NobleMLKEMBridge::decapsulate( $result[ 'ciphertext' ], $pair[ 'secretKey' ] )[ 'sharedSecret' ] );
  }

  public function testHelperErrorsSurfaceAsExceptions (): void {
    $this->expectExceptionMessage( 'Noble ML-KEM error: Unexpected public key length: 3' );
    NobleMLKEMBridge::encapsulate( base64_encode( 'abc' ) );
  }

  public function testStopHelperEndsTheProcess (): void {
    $this->keypair1024();
    $this->assertNotNull( NobleMLKEMBridge::helperPid() );
    NobleMLKEMBridge::stopHelper();
    $this->assertNull( NobleMLKEMBridge::helperPid() );
  }
}
