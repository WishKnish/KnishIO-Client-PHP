<?php

namespace WishKnish\KnishIO\Client\Tests\Storage;

use ReflectionProperty;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\SecureMemory;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Storage\AesGcmSecretStorageProvider;
use WishKnish\KnishIO\Client\Storage\EncryptedSecretPayload;
use WishKnish\KnishIO\Client\Storage\FileStorageBackend;
use WishKnish\KnishIO\Client\Storage\MemorySecretStorageProvider;
use WishKnish\KnishIO\Client\Storage\MemoryStorageBackend;
use WishKnish\KnishIO\Client\Storage\SecretEnvelope;
use WishKnish\KnishIO\Client\Storage\SecretStorageException;
use WishKnish\KnishIO\Client\Storage\SecretStorageMetadata;
use WishKnish\KnishIO\Client\Storage\StorageOptions;
use WishKnish\KnishIO\Client\Storage\SecretStorageProvider;
use WishKnish\KnishIO\Client\Tests\TestCase;

/**
 * Class SecretStorageTest
 *
 * Comprehensive tests for hardware-compatible secret storage envelope encryption,
 * backends, zeroization hygiene, and KnishIOClient integration.
 *
 * @package WishKnish\KnishIO\Client\Tests\Storage
 */
class SecretStorageTest extends TestCase {

  private array $vectors;

  protected function setUp (): void {
    parent::setUp();
    $path = __DIR__ . '/../fixtures/cross-platform-test-vectors.json';
    $this->vectors = json_decode( file_get_contents( $path ), true )[ 'vectors' ];
  }

  /**
   * Decrypt canonical vector secret_storage_envelope.tests[0].payload
   * with passphrase cross-sdk-pass -> MASTER-SECRET-CROSS-SDK-PROBE
   */
  public function testCanonicalVectorDecryptsEverywhere (): void {
    $v = $this->vectors[ 'secret_storage_envelope' ][ 'tests' ][ 0 ];
    $expectedPlaintext = $v[ 'expectedPlaintext' ];
    $passphrase = $v[ 'passphrase' ];
    $bundleHash = $v[ 'bundleHash' ];

    // 1. Direct SecretEnvelope::open test
    $payload = EncryptedSecretPayload::fromArray( $v[ 'payload' ] );
    $plaintext = SecretEnvelope::open( $payload, $passphrase );
    $this->assertSame( $expectedPlaintext, $plaintext, 'Canonical vector must decrypt to expected plaintext' );

    // 2. Provider retrieval test
    $backend = new MemoryStorageBackend();
    $backend->setItem( $v[ 'storageKey' ], json_encode( $v[ 'payload' ] ) );
    $provider = new AesGcmSecretStorageProvider( $backend, $passphrase );

    $this->assertTrue( $provider->hasSecret( $bundleHash ) );
    $retrieved = $provider->retrieveSecret( $bundleHash );
    $this->assertSame( $expectedPlaintext, $retrieved, 'Provider retrieveSecret must unwrap canonical secret' );

    // 3. withSecret callback test
    $callbackInvoked = false;
    $provider->withSecret( $bundleHash, function ( string $secret ) use ( &$callbackInvoked, $expectedPlaintext ) {
      $callbackInvoked = true;
      $this->assertSame( $expectedPlaintext, $secret );
    } );
    $this->assertTrue( $callbackInvoked );
  }

  /**
   * Assert emitted metadata has bundleHash, createdAt, hardwareBacked, providerType,
   * and DOES NOT have bundle_hash or label when unset.
   */
  public function testEmittedMetadataKeysConformToContract (): void {
    $v = $this->vectors[ 'secret_storage_envelope' ][ 'tests' ][ 0 ];
    $requiredKeys = $v[ 'requiredMetadataKeys' ];
    $forbiddenKeys = $v[ 'forbiddenMetadataKeys' ];

    // Test without label (label unset/null)
    $metaWithoutLabel = new SecretStorageMetadata(
      bundleHash: 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
      label: null,
      createdAt: 1788558526546,
      hardwareBacked: false,
      providerType: 'aes-gcm'
    );

    $serialized = $metaWithoutLabel->jsonSerialize();

    foreach ( $requiredKeys as $key ) {
      $this->assertArrayHasKey( $key, $serialized, "Required metadata key {$key} must be present" );
    }

    foreach ( $forbiddenKeys as $key ) {
      $this->assertArrayNotHasKey( $key, $serialized, "Forbidden metadata key {$key} must NOT be present" );
    }

    $this->assertArrayNotHasKey( 'label', $serialized, 'Optional key label must be omitted when unset' );

    // Test with label
    $metaWithLabel = new SecretStorageMetadata(
      bundleHash: 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef',
      label: 'test-label',
      createdAt: 1788558526546,
      hardwareBacked: false,
      providerType: 'aes-gcm'
    );

    $serializedWithLabel = $metaWithLabel->jsonSerialize();
    $this->assertArrayHasKey( 'label', $serializedWithLabel );
    $this->assertSame( 'test-label', $serializedWithLabel[ 'label' ] );

    // Ensure EncryptedSecretPayload serialization respects metadata contract
    $payload = SecretEnvelope::seal( 'secret-data', 'passphrase', $metaWithoutLabel );
    $json = $payload->jsonSerialize();
    $this->assertArrayNotHasKey( 'label', $json[ 'metadata' ] );
    $this->assertSame( 'AES-GCM', $json[ 'algorithm' ] );
    $this->assertSame( 1, $json[ 'version' ] );
  }

  /**
   * Test AES-GCM envelope round trip and CRUD operations with AesGcmSecretStorageProvider
   */
  public function testAesGcmSecretStorageProviderRoundTrip (): void {
    $backend = new MemoryStorageBackend();
    $provider = new AesGcmSecretStorageProvider( $backend, 'default-pass' );

    $bundleHash = 'aabbccddeeff00112233445566778899aabbccddeeff00112233445566778899';
    $secret = 'super-secret-master-key-probe-data-12345';

    $this->assertFalse( $provider->hasSecret( $bundleHash ) );
    $this->assertNull( $provider->retrieveSecret( $bundleHash ) );

    // Store with label
    $provider->storeSecret( $bundleHash, $secret, new StorageOptions( label: 'my-wallet-secret' ) );

    $this->assertTrue( $provider->hasSecret( $bundleHash ) );
    $this->assertSame( $secret, $provider->retrieveSecret( $bundleHash ) );

    // List secrets
    $list = $provider->listSecrets();
    $this->assertCount( 1, $list );
    $this->assertSame( $bundleHash, $list[ 0 ]->bundleHash );
    $this->assertSame( 'my-wallet-secret', $list[ 0 ]->label );
    $this->assertFalse( $list[ 0 ]->hardwareBacked );
    $this->assertSame( 'aes-gcm', $list[ 0 ]->providerType );

    // Custom passphrase in options
    $bundleHash2 = '11223344556677889900aabbccddeeff11223344556677889900aabbccddeeff';
    $provider->storeSecret( $bundleHash2, 'secret-2', new StorageOptions( passphrase: 'custom-pass' ) );

    $this->assertSame( 'secret-2', $provider->retrieveSecret( $bundleHash2, new StorageOptions( passphrase: 'custom-pass' ) ) );

    // Delete secret
    $this->assertTrue( $provider->deleteSecret( $bundleHash ) );
    $this->assertFalse( $provider->hasSecret( $bundleHash ) );
    $this->assertNull( $provider->retrieveSecret( $bundleHash ) );
    $this->assertFalse( $provider->deleteSecret( $bundleHash ) );
  }

  /**
   * Test wrong passphrase triggers SecretStorageException (decryptionFailed)
   */
  public function testAesGcmFailsWithWrongPassphrase (): void {
    $backend = new MemoryStorageBackend();
    $provider = new AesGcmSecretStorageProvider( $backend, 'correct-pass' );
    $bundleHash = 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef';

    $provider->storeSecret( $bundleHash, 'confidential-data' );

    $this->expectException( SecretStorageException::class );
    $this->expectExceptionMessage( 'Failed to decrypt master secret' );

    $provider->retrieveSecret( $bundleHash, new StorageOptions( passphrase: 'incorrect-pass' ) );
  }

  /**
   * Test corrupted payload triggers SecretStorageException
   */
  public function testAesGcmFailsWithCorruptedPayload (): void {
    $backend = new MemoryStorageBackend();
    $provider = new AesGcmSecretStorageProvider( $backend, 'pass' );
    $bundleHash = 'deadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeefdeadbeef';

    $provider->storeSecret( $bundleHash, 'confidential-data' );

    // Corrupt ciphertext in storage
    $raw = $backend->getItem( AesGcmSecretStorageProvider::KEY_PREFIX . $bundleHash );
    $data = json_decode( $raw, true );
    $decodedCt = base64_decode( $data[ 'ciphertext' ] );
    $decodedCt[ 0 ] = chr( ord( $decodedCt[ 0 ] ) ^ 0xff );
    $data[ 'ciphertext' ] = base64_encode( $decodedCt );
    $backend->setItem( AesGcmSecretStorageProvider::KEY_PREFIX . $bundleHash, json_encode( $data ) );

    $this->expectException( SecretStorageException::class );
    $this->expectExceptionMessage( 'Failed to decrypt master secret' );

    $provider->retrieveSecret( $bundleHash );
  }

  /**
   * Test MemorySecretStorageProvider operations
   */
  public function testMemorySecretStorageProvider (): void {
    $provider = new MemorySecretStorageProvider();
    $this->assertSame( 'memory', $provider->getProviderType() );
    $this->assertFalse( $provider->isHardwareBacked() );
    $this->assertTrue( $provider->isAvailable() );

    $bundleHash = 'bundle123';
    $secret = 'in-memory-secret';

    $provider->storeSecret( $bundleHash, $secret, new StorageOptions( label: 'mem' ) );
    $this->assertTrue( $provider->hasSecret( $bundleHash ) );
    $this->assertSame( $secret, $provider->retrieveSecret( $bundleHash ) );

    $list = $provider->listSecrets();
    $this->assertCount( 1, $list );
    $this->assertSame( 'mem', $list[ 0 ]->label );

    $result = $provider->withSecret( $bundleHash, function ( string $s ) {
      return strtoupper( $s );
    } );
    $this->assertSame( 'IN-MEMORY-SECRET', $result );

    $this->assertTrue( $provider->deleteSecret( $bundleHash ) );
    $this->assertFalse( $provider->hasSecret( $bundleHash ) );
  }

  /**
   * Test FileStorageBackend persistence and 0600 permissions
   */
  public function testFileStorageBackendPersistenceAndPermissions (): void {
    $tempDir = sys_get_temp_dir() . '/knishio_test_' . bin2hex( random_bytes( 6 ) );
    $tempFile = $tempDir . '/test_storage.json';

    try {
      $backend = new FileStorageBackend( $tempFile );
      $this->assertSame( $tempFile, $backend->getPath() );
      $this->assertEmpty( $backend->keys() );

      $backend->setItem( 'key1', 'val1' );
      $backend->setItem( 'key2', 'val2' );

      $this->assertSame( 'val1', $backend->getItem( 'key1' ) );
      $this->assertSame( [ 'key1', 'key2' ], $backend->keys() );

      // Verify file permissions (0600) on Unix-like systems
      if ( DIRECTORY_SEPARATOR === '/' ) {
        $perms = substr( sprintf( '%o', fileperms( $tempFile ) ), -4 );
        $this->assertSame( '0600', $perms, 'FileStorageBackend must set 0600 permissions' );
      }

      // Re-load from disk in a fresh instance
      $backendReloaded = new FileStorageBackend( $tempFile );
      $this->assertSame( 'val1', $backendReloaded->getItem( 'key1' ) );
      $this->assertSame( 'val2', $backendReloaded->getItem( 'key2' ) );

      $this->assertTrue( $backendReloaded->removeItem( 'key1' ) );
      $this->assertNull( $backendReloaded->getItem( 'key1' ) );
      $this->assertFalse( $backendReloaded->removeItem( 'non-existent' ) );

      // Reload again to verify removal persisted
      $backendReloaded2 = new FileStorageBackend( $tempFile );
      $this->assertNull( $backendReloaded2->getItem( 'key1' ) );
      $this->assertSame( 'val2', $backendReloaded2->getItem( 'key2' ) );
    } finally {
      if ( file_exists( $tempFile ) ) {
        @unlink( $tempFile );
      }
      if ( is_dir( $tempDir ) ) {
        @rmdir( $tempDir );
      }
    }
  }

  /**
   * Test SecureMemory and zeroize helpers
   */
  public function testSecureMemoryAndZeroizeHelpers (): void {
    // 1. SecureMemory::zeroize
    $str = 'sensitive-master-key-buffer';
    SecureMemory::zeroize( $str );
    $this->assertNull( $str, 'zeroize must set variable reference to null' );

    // 2. Global zeroize helper from helpers.php
    $str2 = 'another-sensitive-buffer';
    zeroize( $str2 );
    $this->assertNull( $str2, 'global zeroize must set variable reference to null' );

    // 3. withSecureString execution and cleanup
    $captured = null;
    $res = SecureMemory::withSecureString( 'temporary-secret', function ( string $s ) use ( &$captured ) {
      $captured = $s;
      return strlen( $s );
    } );
    $this->assertSame( 16, $res );
    $this->assertSame( 'temporary-secret', $captured );

    // 4. Constant time equals
    $this->assertTrue( SecureMemory::constantTimeEquals( 'hash123', 'hash123' ) );
    $this->assertFalse( SecureMemory::constantTimeEquals( 'hash123', 'hash456' ) );
    $this->assertFalse( SecureMemory::constantTimeEquals( 'hash123', 'hash12' ) );
  }

  /**
   * Test KnishIOClient integration:
   * - setSecret auto-syncs into storage
   * - retrieveSecret pulls from storage
   * - hasSecret succeeds when in-memory secret is cleared but storage + bundle remain
   * - createMolecule unwraps just-in-time from storage and signs without retaining cleartext secret
   */
  public function testKnishIOClientStorageIntegration (): void {
    $storage = new AesGcmSecretStorageProvider( new MemoryStorageBackend(), 'passphrase-for-client' );
    $client = new KnishIOClient( 'https://test.knish.io/graphql', secretStorage: $storage );

    $this->assertSame( $storage, $client->getSecretStorage() );
    $this->assertFalse( $client->hasSecret() );

    $secret = Crypto::generateSecret( 'test-seed-for-php-secret-storage' );
    $bundle = Crypto::generateBundleHash( $secret );

    // Store in storage and configure client without setting in-memory cleartext secret
    $storage->storeSecret( $bundle, $secret );
    $client->setSecretStorage( $storage, $bundle );

    $this->assertTrue( $client->hasSecret() );
    $this->assertSame( $bundle, $client->getBundle() );
    $this->assertTrue( $storage->hasSecret( $bundle ) );
    $this->assertSame( $secret, $client->retrieveSecret() );

    // Cleartext secret is null
    $secretProp = new ReflectionProperty( KnishIOClient::class, 'secret' );
    $this->assertNull( $secretProp->getValue( $client ) );

    // createMolecule unwraps secret just-in-time and sets molecule.bundle
    $sourceWallet = new Wallet( $secret, 'USER', position: str_repeat( '0', 64 ) );
    $molecule = $client->createMolecule( sourceWallet: $sourceWallet );
    $this->assertInstanceOf( Molecule::class, $molecule );
    $this->assertSame( $bundle, $molecule->bundle );

    // The client's cleartext secret remains null!
    $this->assertNull( $secretProp->getValue( $client ), 'Client cleartext secret must remain empty after createMolecule' );

    // Add atom and sign
    $atom = new Atom(
      position: $sourceWallet->position,
      walletAddress: $sourceWallet->address,
      isotope: 'C',
      token: 'USER',
      value: '0'
    );
    $molecule->addAtom( $atom );
    $molecule->sign();
    $this->assertNotNull( $molecule->molecularHash );
    $this->assertSame( $bundle, $molecule->bundle );

    // Reset clears everything including storage reference
    $client->reset();
    $this->assertFalse( $client->hasSecret() );
    $this->assertNull( $client->getSecretStorage() );
  }

  /**
   * Test setSecret auto-syncs to storage and reset clears it
   */
  public function testKnishIOClientSetSecretAutoSyncsToStorageAndResetClearsIt (): void {
    $client = new KnishIOClient( 'https://test.knish.io/graphql' );
    $this->assertFalse( $client->hasSecret() );
    $this->assertNull( $client->getSecretStorage() );

    $secret = Crypto::generateSecret( 'test-auto-sync-seed' );
    $bundle = Crypto::generateBundleHash( $secret );

    $client->setSecret( $secret );
    $this->assertTrue( $client->hasSecret() );
    $this->assertSame( $bundle, $client->getBundle() );
    $this->assertSame( $secret, $client->getSecret() );

    $storage = $client->getSecretStorage();
    $this->assertNotNull( $storage );
    $this->assertTrue( $storage->hasSecret( $bundle ) );
    $this->assertSame( $secret, $storage->retrieveSecret( $bundle ) );

    $client->reset();
    $this->assertFalse( $client->hasSecret() );
    $this->assertNull( $client->getSecretStorage() );
  }

  /**
   * Test recovery lifecycle in AesGcmSecretStorageProvider:
   * - storeSecret with recoveryPassphrase creates secondary recovery envelope
   * - listSecrets ignores recovery envelope
   * - primary envelope corruption allows recovery via recoverSecret
   * - deleteSecret deletes both primary and recovery records
   */
  public function testRecoveryLifecycleInAesGcmSecretStorageProvider (): void {
    $backend = new MemoryStorageBackend();
    $provider = new AesGcmSecretStorageProvider( $backend, 'primary-passphrase' );

    $bundle = 'bundle-recovery-aes-test';
    $secret = 'ultra-secure-master-secret-for-recovery';

    $provider->storeSecret( $bundle, $secret, new StorageOptions(
      recoveryPassphrase: 'backup-recovery-pass'
    ) );

    // Both primary and recovery records exist in backend
    $primaryRaw = $backend->getItem( AesGcmSecretStorageProvider::KEY_PREFIX . $bundle );
    $recoveryRaw = $backend->getItem( SecretStorageProvider::RECOVERY_KEY_PREFIX . $bundle );
    $this->assertNotNull( $primaryRaw );
    $this->assertNotNull( $recoveryRaw );

    // Validate recovery envelope metadata
    $recoveryPayload = EncryptedSecretPayload::fromJson( $recoveryRaw );
    $this->assertSame( 'aes-gcm', $recoveryPayload->metadata->providerType );
    $this->assertFalse( $recoveryPayload->metadata->hardwareBacked );
    $this->assertSame( $bundle, $recoveryPayload->metadata->bundleHash );

    // listSecrets only returns primary secret metadata, not recovery
    $list = $provider->listSecrets();
    $this->assertCount( 1, $list );
    $this->assertSame( $bundle, $list[ 0 ]->bundleHash );

    // Simulate corrupting the primary record in backend
    $backend->setItem( AesGcmSecretStorageProvider::KEY_PREFIX . $bundle, 'corrupted-data' );
    $corruptThrew = false;
    try {
      $provider->retrieveSecret( $bundle );
    } catch ( SecretStorageException $e ) {
      $corruptThrew = true;
    }
    $this->assertTrue( $corruptThrew, 'Corrupted primary record must fail retrieveSecret' );
    // Recover secret using recovery passphrase
    $provider->recoverSecret( $bundle, 'backup-recovery-pass' );

    // Direct retrieve now succeeds with primary passphrase
    $retrieved = $provider->retrieveSecret( $bundle );
    $this->assertSame( $secret, $retrieved );

    // Delete secret removes both primary and recovery records
    $this->assertTrue( $provider->deleteSecret( $bundle ) );
    $this->assertNull( $backend->getItem( AesGcmSecretStorageProvider::KEY_PREFIX . $bundle ) );
    $this->assertNull( $backend->getItem( SecretStorageProvider::RECOVERY_KEY_PREFIX . $bundle ) );
  }

  /**
   * Test recovery lifecycle in MemorySecretStorageProvider:
   * - storeSecret with recoveryPassphrase creates secondary recovery envelope
   * - primary secret loss in memory allows recovery via recoverSecret
   * - deleteSecret deletes both memory secret and recovery envelope
   */
  public function testRecoveryLifecycleInMemorySecretStorageProvider (): void {
    $provider = new MemorySecretStorageProvider();
    $bundle = 'mem-bundle-rec';
    $secret = 'mem-secret-value';

    $provider->storeSecret( $bundle, $secret, new StorageOptions(
      recoveryPassphrase: 'mem-recovery-pass'
    ) );

    // Recovery envelope exists in provider backend
    $backend = $provider->getBackend();
    $this->assertNotNull( $backend->getItem( SecretStorageProvider::RECOVERY_KEY_PREFIX . $bundle ) );

    // listSecrets only lists primary secret
    $list = $provider->listSecrets();
    $this->assertCount( 1, $list );
    $this->assertSame( $bundle, $list[ 0 ]->bundleHash );

    // Simulate primary secret wiped by clearing primary memory storage via reflection
    $refProp = new ReflectionProperty( MemorySecretStorageProvider::class, 'secrets' );
    $refProp->setValue( $provider, [] );
    $this->assertFalse( $provider->hasSecret( $bundle ) );
    $this->assertNull( $provider->retrieveSecret( $bundle ) );

    // Recover secret
    $provider->recoverSecret( $bundle, 'mem-recovery-pass' );
    $this->assertTrue( $provider->hasSecret( $bundle ) );
    $this->assertSame( $secret, $provider->retrieveSecret( $bundle ) );

    // Delete secret removes both
    $this->assertTrue( $provider->deleteSecret( $bundle ) );
    $this->assertFalse( $provider->hasSecret( $bundle ) );
    $this->assertNull( $backend->getItem( SecretStorageProvider::RECOVERY_KEY_PREFIX . $bundle ) );
  }

  /**
   * Test recoverSecret failure modes: wrong passphrase, missing record, empty args, corrupted payload
   */
  public function testRecoverSecretFailureModes (): void {
    $backend = new MemoryStorageBackend();
    $provider = new AesGcmSecretStorageProvider( $backend, 'pass' );

    $provider->storeSecret( 'bundleX', 'secretX', new StorageOptions(
      recoveryPassphrase: 'correct-pass'
    ) );

    // 1. Wrong recovery passphrase
    try {
      $provider->recoverSecret( 'bundleX', 'wrong-pass' );
      $this->fail( 'Expected SecretStorageException for wrong recovery passphrase' );
    } catch ( SecretStorageException $e ) {
      $this->assertStringContainsString( 'Authentication tag verification failed or wrong passphrase', $e->getMessage() );
    }

    // 2. Missing recovery record
    try {
      $provider->recoverSecret( 'non-existent-bundle', 'any-pass' );
      $this->fail( 'Expected SecretStorageException for missing recovery record' );
    } catch ( SecretStorageException $e ) {
      $this->assertStringContainsString( 'not found', $e->getMessage() );
    }

    // 3. Empty bundleHash
    try {
      $provider->recoverSecret( '', 'any-pass' );
      $this->fail( 'Expected SecretStorageException for empty bundleHash' );
    } catch ( SecretStorageException $e ) {
      $this->assertStringContainsString( 'Bundle hash cannot be empty', $e->getMessage() );
    }

    // 4. Empty recoveryPassphrase
    try {
      $provider->recoverSecret( 'bundleX', '' );
      $this->fail( 'Expected SecretStorageException for empty recoveryPassphrase' );
    } catch ( SecretStorageException $e ) {
      $this->assertStringContainsString( 'Recovery passphrase cannot be empty', $e->getMessage() );
    }

    // 5. Corrupted recovery payload in backend
    $backend->setItem( SecretStorageProvider::RECOVERY_KEY_PREFIX . 'corrupt-bundle', 'not-valid-json' );
    try {
      $provider->recoverSecret( 'corrupt-bundle', 'correct-pass' );
    } catch ( SecretStorageException $e ) {
      $this->assertStringContainsString( 'Corrupted recovery payload format', $e->getMessage() );
    }
  }
}
