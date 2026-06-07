<?php

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Cross-platform canonical test vectors — verifies PHP SDK against
 * the shared cross-platform-test-vectors.json (Rust reference implementation).
 */
class CrossPlatformVectorsTest extends TestCase {

  private array $vectors;

  protected function setUp (): void {
    parent::setUp();
    $path = __DIR__ . '/fixtures/cross-platform-test-vectors.json';
    $this->vectors = json_decode( file_get_contents( $path ), true )[ 'vectors' ];
  }

  /**
   * @dataProvider shake256Provider
   */
  public function testShake256 ( string $name, string $input, int $outputLength, string $expected ): void {
    // Shake256::hash returns raw bytes; outputLength in the vector file is in bytes
    $result = bin2hex( Shake256::hash( $input, $outputLength ) );
    $this->assertEquals( $expected, $result, "SHAKE256 mismatch for vector: $name" );
  }

  public function shake256Provider (): array {
    $path = __DIR__ . '/fixtures/cross-platform-test-vectors.json';
    $vectors = json_decode( file_get_contents( $path ), true )[ 'vectors' ];
    $cases = [];
    foreach ( $vectors[ 'shake256' ][ 'tests' ] as $test ) {
      $cases[ $test[ 'name' ] ] = [
        $test[ 'name' ],
        $test[ 'input' ],
        $test[ 'outputLength' ],
        $test[ 'expected' ],
      ];
    }
    return $cases;
  }

  /**
   * @dataProvider bundleHashProvider
   */
  public function testBundleHash ( string $name, string $secret, string $expected ): void {
    $result = Crypto::generateBundleHash( $secret );
    $this->assertEquals( $expected, $result, "Bundle hash mismatch for vector: $name" );
  }

  public function bundleHashProvider (): array {
    $path = __DIR__ . '/fixtures/cross-platform-test-vectors.json';
    $vectors = json_decode( file_get_contents( $path ), true )[ 'vectors' ];
    $cases = [];
    foreach ( $vectors[ 'bundle_hash' ][ 'tests' ] as $test ) {
      $cases[ $test[ 'name' ] ] = [
        $test[ 'name' ],
        $test[ 'secret' ],
        $test[ 'expected' ],
      ];
    }
    return $cases;
  }

  /**
   * @dataProvider walletProvider
   */
  public function testWalletAddress ( string $name, string $secret, string $token, string $position, string $expectedBundle, string $expectedAddress ): void {
    // Bundle hash must match
    $bundle = Crypto::generateBundleHash( $secret );
    $this->assertEquals( $expectedBundle, $bundle, "Bundle hash mismatch for wallet: $name" );

    // Create wallet with secret, token, and explicit position
    $wallet = new Wallet( $secret, $token, $position );
    $this->assertEquals( $expectedAddress, $wallet->address, "Wallet address mismatch for wallet: $name" );
  }

  public function walletProvider (): array {
    $path = __DIR__ . '/fixtures/cross-platform-test-vectors.json';
    $vectors = json_decode( file_get_contents( $path ), true )[ 'vectors' ];
    $cases = [];
    foreach ( $vectors[ 'wallet_generation' ][ 'tests' ] as $test ) {
      $cases[ $test[ 'name' ] ] = [
        $test[ 'name' ],
        $test[ 'secret' ],
        $test[ 'token' ],
        $test[ 'position' ],
        $test[ 'expectedBundle' ],
        $test[ 'expectedAddress' ],
      ];
    }
    return $cases;
  }
}
