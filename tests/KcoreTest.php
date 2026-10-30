<?php

namespace WishKnish\KnishIO\Client\Tests;

use InvalidArgumentException;
use WishKnish\KnishIO\Client\Exception\KcoreUnavailableException;
use WishKnish\KnishIO\Client\Libraries\Crypto\SHA3\Sponge;
use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;
use WishKnish\KnishIO\Client\Libraries\Kcore;
use WishKnish\KnishIO\Client\Libraries\PostQuantumCrypto;
use WishKnish\KnishIO\Client\Wallet;

/**
 * The bundled libkcore (Kcore) against the pure-PHP SHA3 sponge it replaces, and the
 * KNISHIO_KCORE modes. Every reference here is computed on Sponge directly, never through
 * Shake256::hash (which itself routes through kcore).
 *
 * The parity tests run when libkcore loads; CI runs the suite with KNISHIO_KCORE=require, where a
 * library that fails to load fails these tests instead of skipping them.
 */
class KcoreTest extends TestCase {

  private static string|false $outerMode;
  private static string|false $outerLib;
  private array $vectors;

  public static function setUpBeforeClass (): void {
    self::$outerMode = getenv( 'KNISHIO_KCORE' );
    self::$outerLib = getenv( 'KNISHIO_KCORE_LIB' );
  }

  protected function setUp (): void {
    Kcore::reset();
    $this->vectors = json_decode( file_get_contents( __DIR__ . '/fixtures/cross-platform-test-vectors.json' ), true )[ 'vectors' ];
  }

  protected function tearDown (): void {
    putenv( self::$outerMode === false ? 'KNISHIO_KCORE' : 'KNISHIO_KCORE=' . self::$outerMode );
    putenv( self::$outerLib === false ? 'KNISHIO_KCORE_LIB' : 'KNISHIO_KCORE_LIB=' . self::$outerLib );
    Kcore::reset();
  }

  private function requireKcore (): void {
    if ( self::$outerMode === 'require' ) {
      $this->assertTrue( Kcore::available(), 'KNISHIO_KCORE=require but libkcore did not load' );
      return;
    }
    if ( !Kcore::available() ) {
      $this->markTestSkipped( 'libkcore not loadable here (no ext-ffi or no bundled library for this platform)' );
    }
  }

  private static function pureShake ( string $data, int $length ): string {
    return Sponge::init( Sponge::SHAKE256 )->absorb( $data )->squeeze( $length );
  }

  /** Wallet::generateAddress, pure: 16 rounds per 128-hex chunk, then the digest sponge. */
  private static function pureAddress ( string $key ): string {
    $digest = Sponge::init( Sponge::SHAKE256 );
    foreach ( str_split( $key, 128 ) as $chunk ) {
      for ( $i = 0; $i < 16; $i++ ) {
        $chunk = bin2hex( self::pureShake( $chunk, 64 ) );
      }
      $digest->absorb( $chunk );
    }
    return bin2hex( self::pureShake( bin2hex( $digest->squeeze( 1024 ) ), 32 ) );
  }

  public function testInvalidModeIsRejected (): void {
    putenv( 'KNISHIO_KCORE=sometimes' );
    $this->expectException( InvalidArgumentException::class );
    Kcore::available();
  }

  public function testOffNeverLoadsTheLibrary (): void {
    putenv( 'KNISHIO_KCORE=off' );
    $this->assertFalse( Kcore::available() );
    $this->assertNull( Kcore::libraryPath() );
    $this->assertNull( Kcore::shake256( 'x', 32 ) );
  }

  public function testRequireFailsLoudlyWhenTheLibraryIsMissing (): void {
    putenv( 'KNISHIO_KCORE=require' );
    putenv( 'KNISHIO_KCORE_LIB=/nonexistent/libkcore.so' );
    try {
      Kcore::available();
      $this->fail( 'available() did not throw' );
    }
    catch ( KcoreUnavailableException $e ) {
      $this->assertStringContainsString( '/nonexistent/libkcore.so', $e->getMessage() );
    }
    // The SDK's hashing surfaces it rather than silently falling back.
    $this->expectException( KcoreUnavailableException::class );
    Shake256::hash( 'x', 32 );
  }

  public function testAutoFallsBackWhenTheLibraryIsMissing (): void {
    putenv( 'KNISHIO_KCORE=auto' );
    putenv( 'KNISHIO_KCORE_LIB=/nonexistent/libkcore.so' );
    $this->assertFalse( Kcore::available() );
    $this->assertSame( self::pureShake( 'knishio', 32 ), Shake256::hash( 'knishio', 32 ) );
  }

  public function testShake256MatchesThePureSponge (): void {
    $this->requireKcore();
    foreach ( [ 1, 32, 64, 1024, 4096 ] as $length ) {
      foreach ( [ '', 'knishio', str_repeat( 'ab', 5000 ) ] as $input ) {
        $this->assertSame( self::pureShake( $input, $length ), Kcore::shake256( $input, $length ), "length {$length}, input " . strlen( $input ) );
      }
    }
  }

  public function testChainsHexWalksEachChainItsOwnCount (): void {
    $this->requireKcore();
    $key = bin2hex( self::pureShake( 'kcore-wheel-check', 1024 ) );
    $reference = '';
    foreach ( str_split( $key, 128 ) as $i => $chunk ) {
      for ( $round = 0; $round < $i; $round++ ) {
        $chunk = bin2hex( self::pureShake( $chunk, 64 ) );
      }
      $reference .= $chunk;
    }
    $this->assertSame( $reference, Kcore::chainsHex( $key, range( 0, 15 ) ) );
  }

  public function testChainsHexRejectsInputKcoreDoesNotHandle (): void {
    $this->requireKcore();
    $key = str_repeat( 'a', 2048 );
    $this->assertNull( Kcore::chainsHex( $key, range( 0, 14 ) ), 'chain count does not match the key' );
    $this->assertNull( Kcore::chainsHex( $key, array_fill( 0, 16, 65 ) ), 'count above 64' );
    $this->assertNull( Kcore::chainsHex( $key, array_fill( 0, 16, -1 ) ), 'negative count' );
    $this->assertNull( Kcore::wotsAddress( str_repeat( 'a', 2047 ) ) );
  }

  public function testWotsAddressesMatchThePureDerivationAtBothOtsVersions (): void {
    $this->requireKcore();
    foreach ( $this->vectors[ 'wallet_generation' ][ 'tests' ] as $t ) {
      $v1 = Wallet::generateKey( $t[ 'secret' ], $t[ 'token' ], $t[ 'position' ], 1 );
      $this->assertSame( $t[ 'expectedAddress' ], Kcore::wotsAddress( $v1 ), "v1 {$t['name']}" );
      $this->assertSame( self::pureAddress( $v1 ), Kcore::wotsAddress( $v1 ), "v1 pure {$t['name']}" );

      $v2 = Wallet::generateKey( $t[ 'secret' ], $t[ 'token' ], $t[ 'position' ], 2 );
      $this->assertSame( 8192, strlen( $v2 ) );
      $this->assertSame( self::pureAddress( $v2 ), Kcore::wotsAddressV2( $v2 ), "v2 pure {$t['name']}" );
    }
  }

  public function testMlkemKeygenKatsAndRoundTrips (): void {
    $this->requireKcore();
    foreach ( [ 1024, 768 ] as $set ) {
      $kg = $this->vectors[ "mlkem{$set}" ][ 'keygen' ];
      $key = Wallet::generateKey( $kg[ 'secret' ], $kg[ 'token' ], $kg[ 'position' ] );
      $pair = Kcore::mlkemKeypair( $set, self::pureShake( $key, 64 ) );
      $this->assertSame( $kg[ 'expectedPubkey' ], base64_encode( $pair[ 'publicKey' ] ), "keygen KAT {$set}" );

      $first = Kcore::mlkemEncaps( $pair[ 'publicKey' ] );
      $this->assertSame( $first[ 'sharedSecret' ], Kcore::mlkemDecaps( $first[ 'ciphertext' ], $pair[ 'secretKey' ] ), "round trip {$set}" );
      $second = Kcore::mlkemEncaps( $pair[ 'publicKey' ] );
      $this->assertNotSame( $first[ 'ciphertext' ], $second[ 'ciphertext' ], "fresh coins on every encaps {$set}" );
    }
    $this->assertNull( Kcore::mlkemEncaps( str_repeat( "\0", 1000 ) ), 'no parameter set has a 1000-byte key' );
  }

  public function testRequireModeRunsMlkemOnKcore (): void {
    $this->requireKcore();
    putenv( 'KNISHIO_KCORE=require' );
    Kcore::reset();
    $this->assertSame( 'kcore', PostQuantumCrypto::backend() );
  }
}
