<?php

namespace WishKnish\KnishIO\Client\Libraries;

use Exception;

/**
 * Class PostQuantumCrypto
 *
 * Provides real ML-KEM-768 key generation using JavaScript @noble/post-quantum
 * via Node.js bridge, ensuring 100% compatibility with JavaScript SDK.
 *
 * This replaces the previous fake implementation that used SHA3-512 hash
 * concatenation with actual FIPS-203 ML-KEM-768 cryptography.
 *
 * @package WishKnish\KnishIO\Client\Libraries
 */
class PostQuantumCrypto
{
    // ML-KEM-768 key sizes (FIPS 203 standard)
    const MLKEM_PUBLIC_KEY_SIZE = 1184; // bytes
    const MLKEM_PRIVATE_KEY_SIZE = 2400; // bytes
    const MLKEM_SEED_SIZE = 64; // bytes
    const MLKEM_CIPHERTEXT_SIZE = 1088; // bytes
    const MLKEM_SHARED_SECRET_SIZE = 32; // bytes

    /**
     * Generate ML-KEM-768 key pair from seed matching JavaScript Noble crypto format
     *
     * Uses @noble/post-quantum via Node.js bridge to ensure exact compatibility
     * with JavaScript SDK implementation. This is deterministic - same seed
     * produces same key pair every time.
     *
     * @param string $seedHex 128 hex characters (64 bytes when converted)
     * @return array ['publicKey' => base64, 'privateKey' => base64]
     * @throws Exception
     */
    public static function generateMLKEMKeyPairFromSeed(string $seedHex): array
    {
        if (strlen($seedHex) !== 128) {
            throw new Exception('Seed must be exactly 128 hex characters for ML-KEM-768');
        }

        // Use Noble crypto via Node.js bridge for guaranteed JavaScript compatibility
        $result = NobleMLKEMBridge::generateMLKEMKeyPairFromSeed($seedHex);

        return [
            'publicKey' => $result['publicKey'],
            'privateKey' => $result['secretKey'] // Bridge returns 'secretKey', normalize to 'privateKey'
        ];
    }

    /**
     * Encapsulate - generate shared secret and ciphertext from public key
     *
     * This is used for key exchange - the sender uses the recipient's public key
     * to generate a shared secret and encrypted ciphertext.
     *
     * @param string $publicKeyBase64 Base64-encoded public key
     * @return array ['ciphertext' => base64, 'sharedSecret' => base64]
     * @throws Exception
     */
    public static function encapsulate(string $publicKeyBase64): array
    {
        return NobleMLKEMBridge::encapsulate($publicKeyBase64);
    }

    /**
     * Decapsulate - recover shared secret from ciphertext using secret key
     *
     * This is used by the recipient to recover the shared secret from the
     * ciphertext using their private key.
     *
     * @param string $ciphertextBase64 Base64-encoded ciphertext
     * @param string $secretKeyBase64 Base64-encoded secret key
     * @return string Base64-encoded shared secret
     * @throws Exception
     */
    public static function decapsulate(string $ciphertextBase64, string $secretKeyBase64): string
    {
        $result = NobleMLKEMBridge::decapsulate($ciphertextBase64, $secretKeyBase64);
        return $result['sharedSecret'];
    }

    /**
     * Convert ML-KEM public key to Base64
     *
     * @param string $publicKey Binary public key
     * @return string Base64 encoded public key
     */
    public static function publicKeyToBase64(string $publicKey): string
    {
        return base64_encode($publicKey);
    }

    /**
     * Convert Base64 to ML-KEM public key
     *
     * @param string $base64Key Base64 encoded public key
     * @return string Binary public key
     */
    public static function publicKeyFromBase64(string $base64Key): string
    {
        return base64_decode($base64Key);
    }

    /**
     * Check if ML-KEM support is available
     *
     * Checks if Node.js and @noble/post-quantum are available
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return NobleMLKEMBridge::isAvailable();
    }

    /**
     * Get implementation status and version information
     *
     * @return array Status information including Node.js version and bridge details
     */
    public static function getStatus(): array
    {
        return NobleMLKEMBridge::getStatus();
    }
}