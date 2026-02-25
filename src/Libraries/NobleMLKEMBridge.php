<?php

namespace WishKnish\KnishIO\Client\Libraries;

use Exception;

/**
 * Class NobleMLKEMBridge
 *
 * Bridges PHP to JavaScript @noble/post-quantum library via Node.js subprocess
 * Ensures 100% compatibility with JavaScript SDK's ML-KEM-768 implementation
 *
 * This is a temporary solution that guarantees cryptographic compatibility
 * until a pure-PHP ML-KEM implementation becomes available.
 *
 * @package WishKnish\KnishIO\Client\Libraries
 */
class NobleMLKEMBridge
{
    private static ?string $bridgeScript = null;
    private static ?string $nodeCommand = null;

    /**
     * Initialize bridge configuration
     *
     * @throws Exception If Node.js or bridge script not found
     */
    private static function initialize(): void
    {
        if (self::$bridgeScript !== null) {
            return;
        }

        // Find Node.js command
        $nodeCommands = ['node', 'nodejs'];
        foreach ($nodeCommands as $cmd) {
            $output = [];
            $returnCode = 0;
            exec("which $cmd 2>/dev/null", $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                self::$nodeCommand = trim($output[0]);
                break;
            }
        }

        if (self::$nodeCommand === null) {
            throw new Exception(
                'Node.js not found. Please install Node.js to use ML-KEM-768 cryptography. ' .
                'Visit https://nodejs.org/ for installation instructions.'
            );
        }

        // Find bridge script
        $possiblePaths = [
            __DIR__ . '/../../bin/noble-mlkem-bridge.js',
            __DIR__ . '/../../../bin/noble-mlkem-bridge.js',
            dirname(dirname(__DIR__)) . '/bin/noble-mlkem-bridge.js',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                self::$bridgeScript = realpath($path);
                break;
            }
        }

        if (self::$bridgeScript === null) {
            throw new Exception(
                'Noble ML-KEM bridge script not found. Expected at: ' . $possiblePaths[0]
            );
        }

        // Verify @noble/post-quantum is installed
        $packageDir = dirname(self::$bridgeScript);
        $nodeModulesPath = dirname($packageDir) . '/node_modules/@noble/post-quantum';

        if (!is_dir($nodeModulesPath)) {
            throw new Exception(
                '@noble/post-quantum not installed. Please run: npm install @noble/post-quantum ' .
                'in directory: ' . dirname($packageDir)
            );
        }
    }

    /**
     * Execute bridge command
     *
     * @param array $args Command arguments
     * @return array Decoded JSON response
     * @throws Exception On command failure
     */
    private static function executeCommand(array $args): array
    {
        self::initialize();

        // Build command with escaped arguments
        $escapedArgs = array_map('escapeshellarg', $args);
        $command = self::$nodeCommand . ' ' . escapeshellarg(self::$bridgeScript) . ' ' . implode(' ', $escapedArgs);

        // Execute command
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        $outputString = implode("\n", $output);

        // Try to find JSON in the output (may have debug/error messages mixed in)
        // Look for lines starting with { which should be the JSON response
        $jsonLine = null;
        foreach ($output as $line) {
            if (strpos($line, '{') === 0) {
                $jsonLine = $line;
                break;
            }
        }

        // Parse JSON response
        $result = $jsonLine ? json_decode($jsonLine, true) : json_decode($outputString, true);

        if ($returnCode !== 0 || $result === null) {
            // Check if output contains error information
            if (is_array($result) && isset($result['error'])) {
                throw new Exception('Noble ML-KEM bridge error: ' . $result['error']);
            }

            throw new Exception(
                'Noble ML-KEM bridge command failed (exit code: ' . $returnCode . ')' . "\n" .
                'Command: ' . $command . "\n" .
                'Output: ' . $outputString
            );
        }

        if (isset($result['error'])) {
            throw new Exception('Noble ML-KEM error: ' . $result['error']);
        }

        return $result;
    }

    /**
     * Generate ML-KEM-768 key pair from seed (deterministic)
     *
     * Uses JavaScript @noble/post-quantum library to ensure exact compatibility
     * with JavaScript SDK implementation.
     *
     * @param string $seedHex 128 hex characters (64 bytes)
     * @return array ['publicKey' => base64, 'secretKey' => base64]
     * @throws Exception
     */
    public static function generateMLKEMKeyPairFromSeed(string $seedHex): array
    {
        if (strlen($seedHex) !== 128) {
            throw new Exception('Seed must be exactly 128 hex characters for ML-KEM-768');
        }

        $result = self::executeCommand(['keygen', $seedHex]);

        if (!isset($result['publicKey']) || !isset($result['secretKey'])) {
            throw new Exception('Invalid response from Noble ML-KEM bridge: missing keys');
        }

        return [
            'publicKey' => $result['publicKey'],
            'secretKey' => $result['secretKey']
        ];
    }

    /**
     * Encapsulate - generate shared secret and ciphertext from public key
     *
     * @param string $publicKeyBase64 Base64-encoded public key
     * @return array ['ciphertext' => base64, 'sharedSecret' => base64]
     * @throws Exception
     */
    public static function encapsulate(string $publicKeyBase64): array
    {
        $result = self::executeCommand(['encaps', $publicKeyBase64]);

        if (!isset($result['ciphertext']) || !isset($result['sharedSecret'])) {
            throw new Exception('Invalid response from Noble ML-KEM bridge: missing encapsulation data');
        }

        return [
            'ciphertext' => $result['ciphertext'],
            'sharedSecret' => $result['sharedSecret']
        ];
    }

    /**
     * Decapsulate - recover shared secret from ciphertext using secret key
     *
     * @param string $ciphertextBase64 Base64-encoded ciphertext
     * @param string $secretKeyBase64 Base64-encoded secret key
     * @return array ['sharedSecret' => base64]
     * @throws Exception
     */
    public static function decapsulate(string $ciphertextBase64, string $secretKeyBase64): array
    {
        $result = self::executeCommand(['decaps', $ciphertextBase64, $secretKeyBase64]);

        if (!isset($result['sharedSecret'])) {
            throw new Exception('Invalid response from Noble ML-KEM bridge: missing shared secret');
        }

        return [
            'sharedSecret' => $result['sharedSecret']
        ];
    }

    /**
     * Check if Noble ML-KEM bridge is available
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        try {
            self::initialize();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get bridge status and version information
     *
     * @return array Status information
     */
    public static function getStatus(): array
    {
        try {
            self::initialize();

            // Get Node.js version
            exec(self::$nodeCommand . ' --version 2>&1', $nodeVersion, $returnCode);

            return [
                'available' => true,
                'nodeCommand' => self::$nodeCommand,
                'nodeVersion' => $returnCode === 0 ? trim($nodeVersion[0] ?? 'unknown') : 'unknown',
                'bridgeScript' => self::$bridgeScript,
                'method' => 'Node.js subprocess bridge to @noble/post-quantum'
            ];
        } catch (Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}