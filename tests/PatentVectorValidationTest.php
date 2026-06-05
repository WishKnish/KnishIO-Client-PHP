<?php

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\MoleculeStructure;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Patent Vector Validation Test
 *
 * Validates the PHP SDK against canonical patent test vectors
 * (canonical-patent-vectors.json) generated from the Rust reference
 * implementation. These vectors provide reduction-to-practice evidence
 * for patent utility prosecution (Claims 1-2, 4-5, 8, 12-14, 21).
 *
 * @package WishKnish\KnishIO\Client\Tests
 */
class PatentVectorValidationTest extends TestCase {

  /**
   * Loaded patent vectors
   */
  private array $vectors;

  protected function setUp (): void {
    parent::setUp();
    $path = __DIR__ . '/fixtures/canonical-patent-vectors.json';
    $json = file_get_contents( $path );
    $this->assertNotFalse( $json, 'Failed to read canonical-patent-vectors.json' );
    $decoded = json_decode( $json, true );
    $this->assertNotNull( $decoded, 'Failed to decode canonical-patent-vectors.json' );
    $this->vectors = $decoded[ 'vectors' ];
  }

  // =========================================================================
  // 0. generateSecret cross-SDK parity (Batch AO) — seed -> 2048 hex secret
  // =========================================================================

  /**
   * Validates that generateSecret( seed ) produces the canonical 2048-char
   * secret, byte-identical to JS/TS/Rust/Python/Kotlin.
   */
  public function testGenerateSecret (): void {
    foreach ( $this->vectors[ 'generate_secret' ][ 'tests' ] as $test ) {
      $secret = Crypto::generateSecret( $test[ 'seed' ] );
      $this->assertEquals(
        $test[ 'length' ],
        strlen( $secret ),
        'generateSecret: length mismatch for ' . $test[ 'name' ]
      );
      $this->assertEquals(
        $test[ 'expectedSecret' ],
        $secret,
        'generateSecret: value mismatch (cross-SDK parity) for ' . $test[ 'name' ]
      );
    }
  }

  // =========================================================================
  // 1. ContinuID Chain Relay (Patent Claims 5, 12-14)
  // =========================================================================

  /**
   * Validates ContinuID identity relay: bundle hash and wallet addresses
   * at two sequential positions derived from the same secret.
   * Position2 = SHAKE256(position1, 256 bits = 32 bytes).
   */
  public function testContinuIdChainRelay (): void {
    $test = $this->vectors[ 'continuid_chain' ][ 'tests' ][ 0 ];

    $secret = $test[ 'secret' ];
    $token = $test[ 'token' ];
    $position1 = $test[ 'position1' ];
    $expectedBundle = $test[ 'expectedBundle' ];
    $expectedAddress1 = $test[ 'expectedAddress1' ];
    $expectedPosition2 = $test[ 'expectedPosition2' ];
    $expectedAddress2 = $test[ 'expectedAddress2' ];

    // Verify bundle hash
    $bundle = Crypto::generateBundleHash( $secret );
    $this->assertEquals(
      $expectedBundle,
      $bundle,
      'ContinuID: bundle hash mismatch'
    );

    // Verify wallet address at position 1
    $wallet1 = new Wallet( $secret, $token, $position1 );
    $this->assertEquals(
      $expectedAddress1,
      $wallet1->address,
      'ContinuID: address at position1 mismatch'
    );

    // Derive position 2 = shake256(position1, 32 bytes) and verify
    $derivedPosition2 = bin2hex( Shake256::hash( $position1, 32 ) );
    $this->assertEquals(
      $expectedPosition2,
      $derivedPosition2,
      'ContinuID: position2 derivation mismatch'
    );

    // Verify wallet address at position 2
    $wallet2 = new Wallet( $secret, $token, $derivedPosition2 );
    $this->assertEquals(
      $expectedAddress2,
      $wallet2->address,
      'ContinuID: address at position2 mismatch'
    );

    // Verify invariants
    $this->assertEquals(
      $wallet1->bundle,
      $wallet2->bundle,
      'ContinuID invariant: both wallets must share the same bundle'
    );
    $this->assertNotEquals(
      $position1,
      $derivedPosition2,
      'ContinuID invariant: positions must differ'
    );
    $this->assertNotEquals(
      $wallet1->address,
      $wallet2->address,
      'ContinuID invariant: addresses must differ'
    );
  }

  // =========================================================================
  // 2. Base17 Enumeration (Patent Claim 5)
  // =========================================================================

  /**
   * @dataProvider base17Provider
   *
   * Validates hex-to-Base17 conversion used in WOTS+ signature indexing.
   * Base17 digits: 0-9, a-g.
   */
  public function testBase17Enumeration ( string $name, string $hexInput, string $expectedBase17, int $normalizedSum ): void {
    // Use Strings::charsetBaseConvert which is the same function used in Atom::hashAtoms()
    $base17Result = Strings::charsetBaseConvert(
      $hexInput,
      16,
      17,
      '0123456789abcdef',
      '0123456789abcdefg'
    );

    if ( $base17Result === 0 || $base17Result === false ) {
      // charsetBaseConvert returns 0 for zero-value input
      if ( $expectedBase17 === str_repeat( '0', 64 ) ) {
        // For all-zero input, pad to 64 zeros
        $base17Result = str_pad( '0', 64, '0', STR_PAD_LEFT );
      } else {
        $this->fail( "Base17 conversion returned unexpected zero/false for vector: $name" );
      }
    }

    // Pad to 64 characters (matching Atom::hashAtoms behavior)
    $base17Padded = str_pad( ( string ) $base17Result, 64, '0', STR_PAD_LEFT );

    $this->assertEquals(
      $expectedBase17,
      $base17Padded,
      "Base17 mismatch for vector: $name"
    );

    // Verify normalized sum invariant (base17 normalized sum must be 0)
    $this->assertEquals(
      $normalizedSum,
      0,
      "Base17 normalized sum must be zero for vector: $name"
    );
  }

  public function base17Provider (): array {
    $path = __DIR__ . '/fixtures/canonical-patent-vectors.json';
    $vectors = json_decode( file_get_contents( $path ), true )[ 'vectors' ];
    $cases = [];
    foreach ( $vectors[ 'base17_enumeration' ][ 'tests' ] as $test ) {
      $cases[ $test[ 'name' ] ] = [
        $test[ 'name' ],
        $test[ 'hexInput' ],
        $test[ 'expectedBase17' ],
        $test[ 'normalizedSum' ],
      ];
    }
    return $cases;
  }

  // =========================================================================
  // 3. Multi-Isotope Molecule (Patent Claims 8, 21)
  // =========================================================================

  /**
   * Validates position derivation and wallet address generation for
   * multiple isotope types (V, M, I) within a single molecule context.
   * Each isotope derives its position from: shake256(sourcePosition + isotopeChar, 256 bits).
   */
  public function testMultiIsotopeMolecule (): void {
    $test = $this->vectors[ 'multi_isotope_molecule' ][ 'tests' ][ 0 ];

    $secret = $test[ 'secret' ];
    $expectedBundle = $test[ 'expectedBundle' ];
    $sourcePosition = $test[ 'invariants' ][ 'source_position' ];
    $isotopes = $test[ 'isotopes' ];

    // Verify bundle hash
    $bundle = Crypto::generateBundleHash( $secret );
    $this->assertEquals(
      $expectedBundle,
      $bundle,
      'Multi-isotope: bundle hash mismatch'
    );

    $allAddresses = [];

    foreach ( $isotopes as $isotopeChar => $isotopeData ) {
      $expectedPosition = $isotopeData[ 'expectedPosition' ];
      $token = $isotopeData[ 'token' ];
      $expectedAddress = $isotopeData[ 'expectedAddress' ];

      // Derive isotope position: shake256(sourcePosition + isotopeChar, 32 bytes)
      $derivedPosition = bin2hex( Shake256::hash( $sourcePosition . $isotopeChar, 32 ) );
      $this->assertEquals(
        $expectedPosition,
        $derivedPosition,
        "Multi-isotope: position derivation mismatch for isotope $isotopeChar"
      );

      // Verify wallet address at derived position
      $wallet = new Wallet( $secret, $token, $derivedPosition );
      $this->assertEquals(
        $expectedAddress,
        $wallet->address,
        "Multi-isotope: address mismatch for isotope $isotopeChar"
      );

      $allAddresses[] = $wallet->address;
    }

    // Verify all addresses are unique
    $this->assertCount(
      count( $allAddresses ),
      array_unique( $allAddresses ),
      'Multi-isotope invariant: all addresses must be unique'
    );
  }

  // =========================================================================
  // 4. BigInt Carry Edge Cases (Patent Claim 5)
  // =========================================================================

  /**
   * @dataProvider bigIntCarryProvider
   *
   * Validates SHAKE256 hash outputs for edge-case inputs that stress
   * BigInt arithmetic boundaries: 65-char hex, max values, boundary values.
   */
  public function testBigIntCarryEdge ( string $name, string $input, int $inputLength, string $expectedShake256, string $expectedBase17OfHash, int $expectedKeyLength ): void {
    // Verify input length
    $this->assertEquals(
      $inputLength,
      strlen( $input ),
      "BigInt carry: input length mismatch for vector: $name"
    );

    // Verify SHAKE256 hash (32 bytes = 256 bits)
    $hash = bin2hex( Shake256::hash( $input, 32 ) );
    $this->assertEquals(
      $expectedShake256,
      $hash,
      "BigInt carry: SHAKE256 hash mismatch for vector: $name"
    );

    // Verify Base17 conversion of the hash
    $base17Result = Strings::charsetBaseConvert(
      $hash,
      16,
      17,
      '0123456789abcdef',
      '0123456789abcdefg'
    );
    $base17Padded = str_pad( ( string ) $base17Result, 64, '0', STR_PAD_LEFT );
    $this->assertEquals(
      $expectedBase17OfHash,
      $base17Padded,
      "BigInt carry: Base17 of hash mismatch for vector: $name"
    );

    // Verify key generation produces expected length
    // Use the input as a secret, 'USER' as token, and a fixed position
    $position = str_repeat( '0', 64 );
    $key = Wallet::generateKey( $input, 'USER', $position );
    $this->assertEquals(
      $expectedKeyLength,
      strlen( $key ),
      "BigInt carry: generated key length mismatch for vector: $name (expected $expectedKeyLength hex chars)"
    );
  }

  public function bigIntCarryProvider (): array {
    $path = __DIR__ . '/fixtures/canonical-patent-vectors.json';
    $vectors = json_decode( file_get_contents( $path ), true )[ 'vectors' ];
    $cases = [];
    foreach ( $vectors[ 'bigint_carry_edge' ][ 'tests' ] as $test ) {
      $cases[ $test[ 'name' ] ] = [
        $test[ 'name' ],
        $test[ 'input' ],
        $test[ 'inputLength' ],
        $test[ 'expectedShake256' ],
        $test[ 'expectedBase17OfHash' ],
        $test[ 'expectedKeyLength' ],
      ];
    }
    return $cases;
  }

  // =========================================================================
  // 5. WOTS+ Roundtrip (Patent Claims 1-2, 5)
  // =========================================================================

  /**
   * Validates the full WOTS+ sign/verify roundtrip.
   *
   * 1. Generates a private key from secret+token+position
   * 2. Verifies the OTS address matches the expected value
   * 3. Signs a molecular hash and verifies fragment count
   * 4. Verifies specific signature fragments match expected values
   * 5. Verifies the signature can be decoded back to the OTS address
   */
  public function testWotsPlusRoundtrip (): void {
    $test = $this->vectors[ 'wots_roundtrip' ][ 'tests' ][ 0 ];

    $secret = $test[ 'secret' ];
    $token = $test[ 'token' ];
    $position = $test[ 'position' ];
    $expectedOtsAddress = $test[ 'expectedOtsAddress' ];
    $molecularHashHex = $test[ 'molecularHashHex' ];
    $molecularHashBase17 = $test[ 'molecularHashBase17' ];
    $expectedFragmentCount = $test[ 'expectedSignatureFragmentCount' ];
    $expectedFragment0 = $test[ 'expectedSignatureFragment0' ];
    $expectedFragment15 = $test[ 'expectedSignatureFragment15' ];

    // Step 1: Generate the private key
    $key = Wallet::generateKey( $secret, $token, $position );
    $this->assertEquals(
      2048,
      strlen( $key ),
      'WOTS+: generated key must be 2048 hex characters (1024 bytes)'
    );

    // Step 2: Verify OTS address (the two-pass protocol wallet address)
    // Hash each 128-char key chunk 16 times, join the public fragments, then
    // digest = SHAKE256(joined, 8192) and address = SHAKE256(digest, 256) --
    // matching Wallet::generateAddress and CheckMolecule::ots (the address the
    // validator verifies against). Wallet::generateAddress is protected, so we
    // read the wallet's ->address (which applies the same two-pass derivation).
    $wallet = new Wallet( $secret, $token, $position );
    $this->assertEquals(
      $expectedOtsAddress,
      $wallet->address,
      'WOTS+: OTS address mismatch'
    );

    // Step 3: Verify the molecular hash base17 conversion
    $base17Result = Strings::charsetBaseConvert(
      $molecularHashHex,
      16,
      17,
      '0123456789abcdef',
      '0123456789abcdefg'
    );
    $base17Padded = str_pad( ( string ) $base17Result, 64, '0', STR_PAD_LEFT );
    $this->assertEquals(
      $molecularHashBase17,
      $base17Padded,
      'WOTS+: molecular hash base17 mismatch'
    );

    // Step 4: Sign the molecular hash using MoleculeStructure::signatureFragments
    // We need a MoleculeStructure with the molecular hash set
    $molStruct = new MoleculeStructure();
    $molStruct->molecularHash = $base17Padded;

    // signatureFragments(key, encode=true) produces the OTS signature
    $signatureFragments = $molStruct->signatureFragments( $key, true );

    // The signature is 2048 hex chars (16 fragments x 128 chars each)
    $this->assertEquals(
      2048,
      strlen( $signatureFragments ),
      'WOTS+: signature must be 2048 hex characters'
    );

    // Split into 128-char chunks and verify count
    $chunks = Strings::chunkSubstr( $signatureFragments, 128 );
    $this->assertCount(
      $expectedFragmentCount,
      $chunks,
      'WOTS+: expected 16 signature fragments'
    );

    // Verify specific fragments
    $this->assertEquals(
      $expectedFragment0,
      $chunks[ 0 ],
      'WOTS+: signature fragment 0 mismatch'
    );
    $this->assertEquals(
      $expectedFragment15,
      $chunks[ 15 ],
      'WOTS+: signature fragment 15 mismatch'
    );

    // Step 5: Verify roundtrip - decode signature fragments back to address
    // signatureFragments(ots, encode=false) reverses the signing process
    $keyFragments = $molStruct->signatureFragments( $signatureFragments, false );

    // Hash the decoded key fragments to get the digest
    $digest = bin2hex( Shake256::hash( $keyFragments, 1024 ) );

    // Hash the digest to get the recovered address
    $recoveredAddress = bin2hex( Shake256::hash( $digest, 32 ) );

    $this->assertEquals(
      $expectedOtsAddress,
      $recoveredAddress,
      'WOTS+: roundtrip verification failed - recovered address does not match OTS address'
    );
  }

  // =========================================================================
  // Additional invariant tests
  // =========================================================================

  /**
   * Verifies that SHAKE256 is deterministic for the ContinuID secret.
   */
  public function testShake256Determinism (): void {
    $test = $this->vectors[ 'continuid_chain' ][ 'tests' ][ 0 ];
    $secret = $test[ 'secret' ];

    $hash1 = bin2hex( Shake256::hash( $secret, 32 ) );
    $hash2 = bin2hex( Shake256::hash( $secret, 32 ) );

    $this->assertEquals(
      $hash1,
      $hash2,
      'SHAKE256 must be deterministic'
    );
  }

  /**
   * Verifies that Wallet::generateKey produces the expected 2048-char key
   * for the ContinuID test vector.
   */
  public function testKeyGenerationLength (): void {
    $test = $this->vectors[ 'continuid_chain' ][ 'tests' ][ 0 ];

    $key = Wallet::generateKey(
      $test[ 'secret' ],
      $test[ 'token' ],
      $test[ 'position1' ]
    );

    $this->assertEquals(
      2048,
      strlen( $key ),
      'generateKey must produce a 2048-character hex string'
    );
    $this->assertTrue(
      ctype_xdigit( $key ),
      'generateKey output must be valid hexadecimal'
    );
  }

  /**
   * Verifies the MoleculeStructure::normalize invariant:
   * the sum of normalized hash values must always be zero.
   */
  public function testNormalizedHashSumIsZero (): void {
    foreach ( $this->vectors[ 'base17_enumeration' ][ 'tests' ] as $test ) {
      $hexInput = $test[ 'hexInput' ];

      // Convert to base17 (same as Atom::hashAtoms does)
      $base17Result = Strings::charsetBaseConvert(
        $hexInput,
        16,
        17,
        '0123456789abcdef',
        '0123456789abcdefg'
      );

      if ( $base17Result === 0 || $base17Result === false ) {
        $base17Padded = str_pad( '0', 64, '0', STR_PAD_LEFT );
      } else {
        $base17Padded = str_pad( ( string ) $base17Result, 64, '0', STR_PAD_LEFT );
      }

      // Set up a MoleculeStructure to use its normalizedHash
      $molStruct = new MoleculeStructure();
      $molStruct->molecularHash = $base17Padded;
      // Add a dummy atom to pass constructor validation
      $molStruct->atoms = [];

      // Use reflection to access the protected normalize and enumerate methods
      $enumerateMethod = new \ReflectionMethod( MoleculeStructure::class, 'enumerate' );
      $enumerateMethod->setAccessible( true );
      $normalizeMethod = new \ReflectionMethod( MoleculeStructure::class, 'normalize' );
      $normalizeMethod->setAccessible( true );

      $enumerated = $enumerateMethod->invoke( null, $base17Padded );
      $normalized = $normalizeMethod->invoke( null, $enumerated );

      $this->assertEquals(
        0,
        array_sum( $normalized ),
        "Normalized hash sum must be zero for vector: {$test['name']}"
      );
    }
  }
}
