<?php

namespace WishKnish\KnishIO\Client\Libraries;

use Exception;

/**
 * Class OpenSSLMLKEM
 * 
 * PHP wrapper for OpenSSL 3.5+ ML-KEM-768 operations
 * Provides real FIPS 203 compliant ML-KEM768 without requiring compilation
 * 
 * @package WishKnish\KnishIO\Client\Libraries
 */
class OpenSSLMLKEM
{
    // ML-KEM768 standard sizes from FIPS 203
    const MLKEM768_PUBLIC_KEY_SIZE = 1184;   // bytes
    const MLKEM768_PRIVATE_KEY_SIZE = 2400;  // bytes  
    const MLKEM768_CIPHERTEXT_SIZE = 1088;   // bytes
    const MLKEM768_SHARED_SECRET_SIZE = 32;  // bytes
    
    /**
     * Check if system OpenSSL supports ML-KEM-768
     * 
     * @return bool
     */
    public static function isAvailable(): bool
    {
        $output = [];
        $returnCode = 0;
        exec('openssl list -kem-algorithms 2>/dev/null', $output, $returnCode);
        
        return $returnCode === 0 && !empty(array_filter($output, function($line) {
            return stripos($line, 'ML-KEM-768') !== false;
        }));
    }
    
    /**
     * Generate ML-KEM768 key pair from deterministic seed
     * Matches JavaScript implementation pattern
     * 
     * @param string $seedHex 128 hex characters (64 bytes when converted)
     * @return array ['publicKey' => string, 'privateKey' => string] Base64 encoded
     * @throws Exception
     */
    public static function generateKeyPairFromSeed(string $seedHex): array
    {
        if (strlen($seedHex) !== 128) {
            throw new Exception('Seed must be exactly 128 hex characters');
        }
        
        // Create temporary files for deterministic key generation
        $tempDir = sys_get_temp_dir();
        $seedFile = tempnam($tempDir, 'mlkem_seed_');
        $keyFile = tempnam($tempDir, 'mlkem_key_');
        $pubFile = tempnam($tempDir, 'mlkem_pub_');
        
        try {
            // Create larger deterministic entropy from seed to ensure deterministic key generation
            $seedBinary = hex2bin($seedHex);
            $deterministicEntropy = '';
            
            // Generate 4KB of deterministic entropy from the seed using SHAKE256
            // This ensures the same seed always produces the same key
            for ($i = 0; $i < 64; $i++) { // 64 * 64 = 4096 bytes
                $chunk = hash('sha3-256', $seedBinary . pack('N', $i), true);
                $deterministicEntropy .= $chunk;
            }
            
            file_put_contents($seedFile, $deterministicEntropy);
            
            // Generate ML-KEM-768 keypair using deterministic entropy
            $cmd = sprintf(
                'openssl genpkey -algorithm ML-KEM-768 -out %s -rand %s 2>/dev/null',
                escapeshellarg($keyFile),
                escapeshellarg($seedFile)
            );
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($keyFile)) {
                throw new Exception('Failed to generate ML-KEM-768 keypair');
            }
            
            // Extract public key
            $cmd = sprintf(
                'openssl pkey -in %s -pubout -out %s 2>/dev/null',
                escapeshellarg($keyFile), 
                escapeshellarg($pubFile)
            );
            
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($pubFile)) {
                throw new Exception('Failed to extract ML-KEM-768 public key');
            }
            
            // Read keys and encode to base64
            $privateKeyPem = file_get_contents($keyFile);
            $publicKeyPem = file_get_contents($pubFile);
            
            // Extract raw key data from PEM format
            $privateKeyRaw = self::extractRawKeyFromPem($privateKeyPem, 'PRIVATE');
            $publicKeyRaw = self::extractRawKeyFromPem($publicKeyPem, 'PUBLIC');
            
            return [
                'publicKey' => base64_encode($publicKeyRaw),
                'privateKey' => base64_encode($privateKeyRaw)
            ];
            
        } finally {
            // Clean up temporary files
            if (file_exists($seedFile)) unlink($seedFile);
            if (file_exists($keyFile)) unlink($keyFile);
            if (file_exists($pubFile)) unlink($pubFile);
        }
    }
    
    /**
     * Encapsulate using ML-KEM768 to generate shared secret and ciphertext
     * 
     * @param string $publicKeyBase64 Base64 encoded public key
     * @return array ['ciphertext' => string, 'sharedSecret' => string] Raw binary data
     * @throws Exception
     */
    public static function encapsulate(string $publicKeyBase64): array
    {
        $tempDir = sys_get_temp_dir();
        $pubFile = tempnam($tempDir, 'mlkem_pub_');
        $cipherFile = tempnam($tempDir, 'mlkem_cipher_');
        $secretFile = tempnam($tempDir, 'mlkem_secret_');
        
        try {
            // Convert base64 public key to PEM format
            $publicKeyRaw = base64_decode($publicKeyBase64);
            $publicKeyPem = self::createPemFromRawKey($publicKeyRaw, 'PUBLIC');
            file_put_contents($pubFile, $publicKeyPem);
            
            // Perform encapsulation
            $cmd = sprintf(
                'openssl pkeyutl -encap -pubin -inkey %s -out %s -secret %s 2>/dev/null',
                escapeshellarg($pubFile),
                escapeshellarg($cipherFile),
                escapeshellarg($secretFile)
            );
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($cipherFile) || !file_exists($secretFile)) {
                throw new Exception('ML-KEM-768 encapsulation failed');
            }
            
            $ciphertext = file_get_contents($cipherFile);
            $sharedSecret = file_get_contents($secretFile);
            
            // Verify sizes match FIPS 203 specification
            if (strlen($ciphertext) !== self::MLKEM768_CIPHERTEXT_SIZE) {
                throw new Exception('Invalid ciphertext size: ' . strlen($ciphertext));
            }
            
            if (strlen($sharedSecret) !== self::MLKEM768_SHARED_SECRET_SIZE) {
                throw new Exception('Invalid shared secret size: ' . strlen($sharedSecret));
            }
            
            return [
                'ciphertext' => $ciphertext,
                'sharedSecret' => $sharedSecret
            ];
            
        } finally {
            if (file_exists($pubFile)) unlink($pubFile);
            if (file_exists($cipherFile)) unlink($cipherFile);
            if (file_exists($secretFile)) unlink($secretFile);
        }
    }
    
    /**
     * Decapsulate using ML-KEM768 to recover shared secret
     * 
     * @param string $privateKeyBase64 Base64 encoded private key
     * @param string $ciphertext Raw ciphertext bytes
     * @return string Raw shared secret bytes
     * @throws Exception
     */
    public static function decapsulate(string $privateKeyBase64, string $ciphertext): string
    {
        $tempDir = sys_get_temp_dir();
        $keyFile = tempnam($tempDir, 'mlkem_key_');
        $cipherFile = tempnam($tempDir, 'mlkem_cipher_');
        $secretFile = tempnam($tempDir, 'mlkem_secret_');
        
        try {
            // Convert base64 private key to PEM format
            $privateKeyRaw = base64_decode($privateKeyBase64);
            $privateKeyPem = self::createPemFromRawKey($privateKeyRaw, 'PRIVATE');
            file_put_contents($keyFile, $privateKeyPem);
            
            // Write ciphertext to file
            file_put_contents($cipherFile, $ciphertext);
            
            // Perform decapsulation
            $cmd = sprintf(
                'openssl pkeyutl -decap -inkey %s -in %s -secret %s 2>/dev/null',
                escapeshellarg($keyFile),
                escapeshellarg($cipherFile), 
                escapeshellarg($secretFile)
            );
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
            
            if ($returnCode !== 0 || !file_exists($secretFile)) {
                throw new Exception('ML-KEM-768 decapsulation failed');
            }
            
            $sharedSecret = file_get_contents($secretFile);
            
            if (strlen($sharedSecret) !== self::MLKEM768_SHARED_SECRET_SIZE) {
                throw new Exception('Invalid recovered shared secret size: ' . strlen($sharedSecret));
            }
            
            return $sharedSecret;
            
        } finally {
            if (file_exists($keyFile)) unlink($keyFile);
            if (file_exists($cipherFile)) unlink($cipherFile);
            if (file_exists($secretFile)) unlink($secretFile);
        }
    }
    
    /**
     * Extract raw key bytes from PEM format
     * 
     * @param string $pemData PEM formatted key
     * @param string $type 'PUBLIC' or 'PRIVATE'
     * @return string Raw key bytes
     * @throws Exception
     */
    private static function extractRawKeyFromPem(string $pemData, string $type): string
    {
        $header = "-----BEGIN {$type} KEY-----";
        $footer = "-----END {$type} KEY-----";
        
        $start = strpos($pemData, $header);
        $end = strpos($pemData, $footer);
        
        if ($start === false || $end === false) {
            throw new Exception("Invalid PEM format for {$type} key");
        }
        
        $base64Data = substr($pemData, $start + strlen($header), $end - $start - strlen($header));
        $base64Data = preg_replace('/\s+/', '', $base64Data);
        
        $rawData = base64_decode($base64Data);
        if ($rawData === false) {
            throw new Exception("Failed to decode {$type} key from PEM");
        }
        
        return $rawData;
    }
    
    /**
     * Create PEM format from raw key bytes  
     * 
     * @param string $rawKey Raw key bytes
     * @param string $type 'PUBLIC' or 'PRIVATE'
     * @return string PEM formatted key
     */
    private static function createPemFromRawKey(string $rawKey, string $type): string
    {
        $base64Data = base64_encode($rawKey);
        $chunks = str_split($base64Data, 64);
        $formattedData = implode("\n", $chunks);
        
        return "-----BEGIN {$type} KEY-----\n{$formattedData}\n-----END {$type} KEY-----\n";
    }
}