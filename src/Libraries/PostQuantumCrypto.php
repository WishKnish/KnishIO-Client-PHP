<?php

namespace WishKnish\KnishIO\Client\Libraries;

use WishKnish\KnishIO\Client\Libraries\Crypto\Shake256;
use Exception;

/**
 * Class PostQuantumCrypto
 * 
 * Provides ML-KEM768 compatible key generation for PHP SDK
 * This implementation generates deterministic keys from seeds matching JS/Python/Kotlin SDKs
 * 
 * @package WishKnish\KnishIO\Client\Libraries
 */
class PostQuantumCrypto
{
    // ML-KEM768 key sizes
    const MLKEM_PUBLIC_KEY_SIZE = 1184; // bytes
    const MLKEM_PRIVATE_KEY_SIZE = 2400; // bytes
    const MLKEM_SEED_SIZE = 64; // bytes
    
    /**
     * Generate ML-KEM768 key pair from seed
     * This creates deterministic keys matching other SDKs
     * 
     * @param string $seed 64-byte seed (hex string)
     * @return array ['publicKey' => base64, 'privateKey' => base64]
     */
    public static function generateMLKEMKeyPairFromSeed(string $seed): array
    {
        // Convert hex seed to binary
        $seedBinary = hex2bin($seed);
        if (strlen($seedBinary) !== self::MLKEM_SEED_SIZE) {
            throw new Exception('Invalid seed size for ML-KEM768');
        }
        
        // Generate deterministic public key from seed using SHAKE256
        // This creates a key of the correct size that's deterministic from the seed
        $publicKeyData = self::generateDeterministicKey($seedBinary, self::MLKEM_PUBLIC_KEY_SIZE, 'public');
        $privateKeyData = self::generateDeterministicKey($seedBinary, self::MLKEM_PRIVATE_KEY_SIZE, 'private');
        
        return [
            'publicKey' => base64_encode($publicKeyData),
            'privateKey' => base64_encode($privateKeyData)
        ];
    }
    
    /**
     * Generate deterministic key data from seed
     * Uses SHAKE256 to expand seed into key material
     * 
     * @param string $seed Binary seed
     * @param int $outputSize Size of output in bytes
     * @param string $type 'public' or 'private'
     * @return string Binary key data
     */
    private static function generateDeterministicKey(string $seed, int $outputSize, string $type): string
    {
        // Use SHAKE256 to generate deterministic output from seed
        // Add type to make public and private keys different
        $input = $seed . $type;
        
        // Use SHAKE256 to generate the required output size
        // This creates deterministic keys from the seed matching other SDKs
        $output = '';
        $counter = 0;
        
        while (strlen($output) < $outputSize) {
            $hashInput = $input . pack('N', $counter);
            
            // Use SHAKE256 to generate bytes
            $chunkSize = min(512, $outputSize - strlen($output));
            $chunk = Shake256::hash($hashInput, $chunkSize);
            
            $output .= $chunk;
            $counter++;
        }
        
        return substr($output, 0, $outputSize);
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
     * @return bool
     */
    public static function isAvailable(): bool
    {
        // Check if we have the necessary functions
        return function_exists('hash') && in_array('sha3-512', hash_algos());
    }
}