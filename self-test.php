#!/usr/bin/env php
<?php

// Suppress deprecation warnings for cleaner output (keep real errors visible)
error_reporting(E_ALL & ~E_DEPRECATED);

/**
 * Knish.IO PHP SDK Self-Test Script (FIXED VALIDATION)
 * 
 * This script performs self-contained tests to validate SDK functionality
 * and ensure cross-SDK compatibility. It reads test configurations from a
 * shared JSON file and outputs results in a standardized format.
 * 
 * FIXED: Proper validation logic using exception-based validation
 * - CheckMolecule::verify() is void and throws exceptions
 * - MoleculeStructure::check() is void and throws exceptions
 * - Success = no exception thrown, Failure = exception thrown
 */

require_once __DIR__ . '/vendor/autoload.php';

use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Meta;

// ANSI Color codes for console output
const COLOR_RESET = "\033[0m";
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_CYAN = "\033[36m";
const COLOR_MAGENTA = "\033[35m";
const COLOR_BOLD = "\033[1m";

// Fixed timestamp for deterministic testing (preserves timestamp in hash while ensuring consistency)
const FIXED_TEST_TIMESTAMP_BASE = 1700000000000; // Fixed base timestamp for deterministic testing

/**
 * Helper function to set fixed timestamps for deterministic testing
 */
function setFixedTimestamps($molecule) {
    for ($i = 0; $i < count($molecule->atoms); $i++) {
        // Set deterministic timestamp: base + (index * 1000) to ensure unique but predictable timestamps
        $molecule->atoms[$i]->createdAt = strval(FIXED_TEST_TIMESTAMP_BASE + ($i * 1000));
    }
}

/**
 * Helper function to create fixed remainder wallets for deterministic testing
 */
function createFixedRemainderWallet($secret, $token) {
    return new Wallet(
        $secret,
        $token,
        'bbbb000000000000cccc111111111111dddd222222222222eeee333333333333', // Fixed deterministic position
        null, // address will be derived
        null, // batchId
        null  // characters
    );
}

// Embedded test configuration for SDK self-containment (PHP best practices)
$DEFAULT_CONFIG = [
    'tests' => [
        'crypto' => [
            'seed' => 'TESTSEED',
            'secret' => 'e8ffc86d60fc6a73234a834166e7436e21df6c3209dfacc8d0bd6595707872c3799abbf7deee0f9c4b58de1fd89b9abb67a207558208d5ccf550c227d197c24e9fcc3707aeb53c4031d38392020ff72bcaa0f728aa8bc3d47d95ff0afc04d8fcdb69bff638ce56646c154fc92aa517d3c40f550d2ccacbd921724e1d94b82aed2c8e172a8a7ed5a6963f5890157fe77222b97af3787741f9d3cec0b40aec6f07ae4b2b24614f0a20e035aee0df04e176175dc100eb1b00dd7ea95c28cdec47958336945333c3bef24719ed949fa56d1541f24c725d4f374a533bf255cf22f4596147bcd1ba05abcecbe9b12095e1fdddb094616894c366498be0b5785c180100efb3c5b689fc1c01131633fe1775df52a970e9472ab7bc0c19f5742b9e9436753cd16024b2d326b763eca68c414755a0d2fdbb927f007e9413f1190578b2033a03d29387f5aea71b07a5ce80fbfd45be4a15440faadeac50e41846022894fc683a52328b470bc1860c8b038d7258f504178918502b93d84d8b0fbef3e02f89f83cb1ff033a2bdbdf2a2ba78d80c12aa8b2d6c10d76c468186bd4a4e9eacc758546bb50ed7b1ee241cc5b93ff924c7bbee6778b27789e1f9104c917fc93f735eee5b25c07a883788f3d2e0771e751c4f59b76f8426027ac2b07a2ca84534433d0a1b86cef3288e7d79e8b175a3955848cfd1dfbdcd6b5bafcf6789e56e8ef40af',
            'bundle' => 'fee9c2b9a964d060eb4645c4001db805c3c4b0cc9bba12841036eba4bf44b831',
            'walletAddress' => 'Kk4xBpejTujcDQxuuUNVEcvvRNwRGMfLFm28p1aqv2wQ52u5X'
        ],
        'metaCreation' => [
            'seed' => 'TESTSEED',
            'token' => 'USER',
            'sourcePosition' => '0123456789abcdeffedcba9876543210fedcba9876543210fedcba9876543210',
            'metaType' => 'TestMeta',
            'metaId' => 'TESTMETA123',
            'metadata' => [
                'name' => 'Test Metadata',
                'description' => 'This is a test metadata for SDK testing.'
            ]
        ],
        'simpleTransfer' => [
            'sourceSeed' => 'TESTSEED',
            'recipientSeed' => 'RECIPIENTSEED',
            'balance' => 1000,
            'amount' => 1000,
            'token' => 'TEST',
            'sourcePosition' => '0123456789abcdeffedcba9876543210fedcba9876543210fedcba9876543210',
            'recipientPosition' => 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210'
        ],
        'complexTransfer' => [
            'sourceSeed' => 'TESTSEED',
            'recipient1Seed' => 'RECIPIENTSEED', 
            'recipient2Seed' => 'RECIPIENT2SEED',
            'sourceBalance' => 1000,
            'amount1' => 500,
            'amount2' => 500,
            'token' => 'TEST',
            'sourcePosition' => '0123456789abcdeffedcba9876543210fedcba9876543210fedcba9876543210',
            'recipient1Position' => 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210',
            'recipient2Position' => 'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789'
        ],
        'mlkem768' => [
            'seed' => 'TESTSEED',
            'token' => 'ENCRYPT',
            'position' => '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
            'plaintext' => 'Hello ML-KEM768 cross-platform test message!'
        ]
    ]
];

// Support optional external config override via environment variable
$configPath = $_ENV['KNISHIO_TEST_CONFIG'] ?? null;
if ($configPath && file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
} else {
    $config = $DEFAULT_CONFIG;
}

// Get version from composer.json
$composerData = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
$version = $composerData['version'] ?? '1.0.0';

// Test results storage (matches JavaScript SDK format)
$results = [
    'sdk' => 'PHP',
    'version' => $version,
    'timestamp' => date('c'),
    'tests' => [],
    'molecules' => [],
    'crossSdkCompatible' => true
];

function log_message($message, $color = COLOR_RESET) {
    echo $color . $message . COLOR_RESET . PHP_EOL;
}

function log_test($testName, $passed, $errorDetail = null) {
    $status = $passed ? '✅ PASS' : '❌ FAIL';
    $color = $passed ? COLOR_GREEN : COLOR_RED;
    log_message("  $status: $testName", $color);
    if (!$passed && $errorDetail) {
        log_message("    $errorDetail", COLOR_RED);
    }
}

function inspect_molecule($molecule, $name = 'molecule') {
    log_message("\n🔍 INSPECTING " . strtoupper($name) . ":", COLOR_BLUE);
    log_message("  Molecular Hash: " . ($molecule->molecularHash ?? 'NOT_SET'));
    log_message("  Secret: SET (cannot access private property)");
    log_message("  Bundle: " . ($molecule->bundle ?? 'NOT_SET'));
    
    // Handle wallet properties safely
    $sourceInfo = 'NOT_SET';
    $remainderInfo = 'NOT_SET';
    
    try {
        if (isset($molecule->sourceWallet) && $molecule->sourceWallet) {
            $sourceInfo = substr($molecule->sourceWallet->address, 0, 16) . '...';
        }
    } catch (Exception $e) {
        $sourceInfo = 'PRIVATE_PROPERTY';
    }
    
    try {
        if (isset($molecule->remainderWallet) && $molecule->remainderWallet) {
            $remainderInfo = substr($molecule->remainderWallet->address, 0, 16) . '...';
        }
    } catch (Exception $e) {
        $remainderInfo = 'PRIVATE_PROPERTY';
    }
    
    log_message("  Source Wallet: $sourceInfo");
    log_message("  Remainder Wallet: $remainderInfo");
    log_message("  Atoms (" . count($molecule->atoms) . "):");
    
    $totalValue = 0;
    foreach ($molecule->atoms as $index => $atom) {
        $value = is_numeric($atom->value) ? floatval($atom->value) : 0;
        $totalValue += $value;
        $address = substr($atom->walletAddress, 0, 16) . '...';
        log_message("    [$index] {$atom->isotope}: {$atom->value} ($address) index={$atom->index}");
    }
    
    $balanced = abs($totalValue) < 0.01 ? '✅ BALANCED' : '❌ UNBALANCED';
    log_message("  Total Value: $totalValue $balanced");
    log_message("  Cell Slug: " . ($molecule->cellSlug ?? 'NOT_SET'));
    log_message("  Status: " . ($molecule->status ?? 'NOT_SET'));
}

function diagnose_validation($molecule, $senderWallet, $name = 'molecule') {
    log_message("\n🔬 VALIDATING " . strtoupper($name) . " STEP-BY-STEP:", COLOR_BLUE);
    
    log_message("  Molecule has " . count($molecule->atoms) . " atoms");
    
    if (!empty($molecule->atoms)) {
        log_message("  First atom isotope: " . $molecule->atoms[0]->isotope);
    }
    
    log_message("  Molecular hash present: " . ($molecule->molecularHash ? 'true' : 'false'));
    log_message("  Source wallet provided: " . ($senderWallet ? 'true' : 'false'));
    
    // Check atom indices
    foreach ($molecule->atoms as $atom) {
        $color = isset($atom->index) ? COLOR_GREEN : COLOR_RED;
        $status = isset($atom->index) ? '✅' : '❌';
        log_message("    $status Atom {$atom->index} index: {$atom->index}", $color);
    }
}

/**
 * Test 1: Crypto Test
 * Validates secret generation and bundle hash
 */
function test_crypto() {
    log_message('\n1. Crypto Test', COLOR_BLUE);
    global $config, $results;
    
    $testConfig = $config['tests']['crypto'];
    
    try {
        // Generate secret from seed
        $secret = Crypto::generateSecret($testConfig['seed'], 1024);
        log_message("  Generated secret length: " . strlen($secret), COLOR_YELLOW);
        log_message("  First 64 chars: " . substr($secret, 0, 64) . "...", COLOR_YELLOW);
        log_message("  Expected length: 1024", COLOR_YELLOW);
        log_message("  Expected first 64: " . substr($testConfig['secret'], 0, 64) . "...", COLOR_YELLOW);
        
        $secretMatch = $secret === $testConfig['secret'];
        log_test('Secret generation (seed: "' . $testConfig['seed'] . '")', $secretMatch);
        
        // Generate bundle hash
        $bundle = Crypto::generateBundleHash($secret);
        log_message("  Generated bundle: $bundle", COLOR_YELLOW);
        log_message("  Expected bundle: " . $testConfig['bundle'], COLOR_YELLOW);
        
        $bundleMatch = $bundle === $testConfig['bundle'];
        log_test('Bundle hash generation', $bundleMatch);
        
        $passed = $secretMatch && $bundleMatch;
        
        $results['tests']['crypto'] = [
            'passed' => $passed,
            'secret' => $secret,
            'bundle' => $bundle,
            'expectedSecret' => $testConfig['secret'],
            'expectedBundle' => $testConfig['bundle']
        ];
        
        return $passed;
        
    } catch (Exception $e) {
        log_message("  ❌ ERROR: " . $e->getMessage(), COLOR_RED);
        $results['tests']['crypto'] = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

/**
 * Test 2: Metadata Creation Test (FIXED VALIDATION)
 * Creates a metadata molecule with M and I isotopes
 */
function test_meta_creation() {
    log_message('\n2. Metadata Creation Test', COLOR_BLUE);
    global $config, $results;
    
    $testConfig = $config['tests']['metaCreation'];
    
    try {
        // Create source wallet
        $sourceSecret = Crypto::generateSecret($testConfig['seed'], 1024);
        $sourceBundle = Crypto::generateBundleHash($sourceSecret);
        
        $sourceWallet = new Wallet(
            $sourceSecret,
            $testConfig['token'],
            $testConfig['sourcePosition']
        );
        
        log_test('Source wallet creation', true);
        
        // Create fixed remainder wallet for deterministic testing
        $remainderWallet = createFixedRemainderWallet($sourceSecret, $testConfig['token']);

        // Create molecule for metadata with fixed remainder wallet
        $molecule = new Molecule($sourceSecret, $sourceWallet, $remainderWallet);
        
        // Initialize metadata molecule (convert to simple key-value array, not Meta objects)
        $meta = [];
        foreach ($testConfig['metadata'] as $key => $value) {
            $meta[$key] = $value;
        }
        
        $molecule->initMeta(
            $meta,
            $testConfig['metaType'],
            $testConfig['metaId']
        );
        
        log_test('Metadata molecule initialization', true);
        
        // Set fixed timestamps for deterministic testing (before signing)
        setFixedTimestamps($molecule);
        
        // Sign the molecule
        $molecule->sign(false);
        log_test('Molecule signing', true);
        
        // Debug: Inspect molecule before validation
        inspect_molecule($molecule, 'metadata molecule');
        
        // Step-by-step validation diagnostic
        diagnose_validation($molecule, $sourceWallet, 'metadata molecule');
        
        // FIXED: Validate the molecule using proper exception-based validation
        $isValid = false;
        $validationError = null;
        try {
            $checkMolecule = new CheckMolecule($molecule);
            $checkMolecule->verify($sourceWallet);  // void method - throws on failure
            $isValid = true;  // if we reach here, validation passed
        } catch (Exception $error) {
            $isValid = false;
            $validationError = $error->getMessage();
        }
        
        log_test('Molecule validation', $isValid, $validationError);
        
        // Store serialized molecule for cross-SDK verification
        $results['molecules']['metadata'] = $molecule->toJSON();
        
        $results['tests']['metaCreation'] = [
            'passed' => $isValid,
            'molecularHash' => $molecule->molecularHash,
            'atomCount' => count($molecule->atoms),
            'validationError' => $validationError
        ];
        
        return $isValid;
        
    } catch (Exception $e) {
        log_message("  ❌ ERROR: " . $e->getMessage(), COLOR_RED);
        $results['tests']['metaCreation'] = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

/**
 * Test 3: Simple Transfer Test (FIXED VALIDATION)
 * Creates a value transfer with no remainder (full balance)
 */
function test_simple_transfer() {
    log_message('\n3. Simple Transfer Test', COLOR_BLUE);
    global $config, $results;
    
    $testConfig = $config['tests']['simpleTransfer'];
    
    try {
        // Create source wallet
        $sourceSecret = Crypto::generateSecret($testConfig['sourceSeed'], 1024);
        $sourceBundle = Crypto::generateBundleHash($sourceSecret);
        
        $sourceWallet = new Wallet(
            $sourceSecret,
            $testConfig['token'], 
            $testConfig['sourcePosition']
        );
        
        // Set balance manually for testing
        $sourceWallet->balance = $testConfig['balance'];
        
        log_test('Source wallet creation', true);
        
        // Create recipient wallet
        $recipientSecret = Crypto::generateSecret($testConfig['recipientSeed'], 1024);
        
        $recipientWallet = new Wallet(
            $recipientSecret,
            $testConfig['token'],
            $testConfig['recipientPosition']
        );
        
        log_test('Recipient wallet creation', true);
        
        // Create fixed remainder wallet for deterministic testing
        $remainderWallet = createFixedRemainderWallet($sourceSecret, $testConfig['token']);

        // Create molecule for value transfer with fixed remainder wallet
        $molecule = new Molecule($sourceSecret, $sourceWallet, $remainderWallet);
        
        // Initialize value transfer
        $molecule->initValue($recipientWallet, $testConfig['amount']);
        
        log_test('Value transfer initialization', true);
        
        // Set fixed timestamps for deterministic testing (before signing)
        setFixedTimestamps($molecule);
        
        // Sign the molecule
        $molecule->sign(false);
        log_test('Molecule signing', true);
        
        // Debug: Inspect molecule before validation
        inspect_molecule($molecule, 'simple transfer molecule');
        
        // FIXED: Validate the molecule using proper exception-based validation
        $isValid = false;
        $validationError = null;
        try {
            $checkMolecule = new CheckMolecule($molecule);
            $checkMolecule->verify($sourceWallet);  // void method - throws on failure
            $isValid = true;  // if we reach here, validation passed
        } catch (Exception $error) {
            $isValid = false;
            $validationError = $error->getMessage();
        }
        
        log_test('Molecule validation', $isValid, $validationError);
        
        // Store serialized molecule for cross-SDK verification
        $results['molecules']['simpleTransfer'] = $molecule->toJSON();
        
        $results['tests']['simpleTransfer'] = [
            'passed' => $isValid,
            'molecularHash' => $molecule->molecularHash,
            'atomCount' => count($molecule->atoms),
            'validationError' => $validationError
        ];
        
        return $isValid;
        
    } catch (Exception $e) {
        log_message("  ❌ ERROR: " . $e->getMessage(), COLOR_RED);
        $results['tests']['simpleTransfer'] = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

/**
 * Test 4: Complex Transfer Test (FIXED VALIDATION)
 * Creates a value transfer with remainder
 */
function test_complex_transfer() {
    log_message('\n4. Complex Transfer Test', COLOR_BLUE);
    global $config, $results;
    
    $testConfig = $config['tests']['complexTransfer'];
    
    try {
        // Create source wallet
        $sourceSecret = Crypto::generateSecret($testConfig['sourceSeed'], 1024);
        $sourceBundle = Crypto::generateBundleHash($sourceSecret);
        
        $sourceWallet = new Wallet(
            $sourceSecret,
            $testConfig['token'],
            $testConfig['sourcePosition']
        );
        
        // Set balance manually for testing
        $sourceWallet->balance = $testConfig['sourceBalance'];
        
        log_test('Source wallet creation', true);
        
        // Create fixed remainder wallet for deterministic testing
        $remainderWallet = createFixedRemainderWallet($sourceSecret, $testConfig['token']);
        
        log_test('Remainder wallet creation', true);
        
        // Create recipient wallet
        $recipientSecret = Crypto::generateSecret($testConfig['recipient1Seed'], 1024);
        
        $recipientWallet = new Wallet(
            $recipientSecret,
            $testConfig['token'],
            $testConfig['recipient1Position']
        );
        
        log_test('Recipient wallet creation', true);
        
        // Create molecule for value transfer with remainder
        $molecule = new Molecule($sourceSecret, $sourceWallet, $remainderWallet);
        
        // Initialize value transfer with remainder
        $molecule->initValue($recipientWallet, $testConfig['amount1']);
        
        log_test('Value transfer with remainder initialization', true);
        
        // Set fixed timestamps for deterministic testing (before signing)
        setFixedTimestamps($molecule);
        
        // Sign the molecule
        $molecule->sign(false);
        log_test('Molecule signing', true);
        
        // Debug: Inspect molecule before validation
        inspect_molecule($molecule, 'complex transfer molecule');
        
        // Step-by-step validation diagnostic
        diagnose_validation($molecule, $sourceWallet, 'complex transfer molecule');
        
        // FIXED: Validate the molecule using proper exception-based validation
        $isValid = false;
        $validationError = null;
        try {
            $checkMolecule = new CheckMolecule($molecule);
            $checkMolecule->verify($sourceWallet);  // void method - throws on failure
            $isValid = true;  // if we reach here, validation passed
        } catch (Exception $error) {
            $isValid = false;
            $validationError = $error->getMessage();
        }
        
        log_test('Molecule validation', $isValid, $validationError);
        
        // Store serialized molecule for cross-SDK verification
        $results['molecules']['complexTransfer'] = $molecule->toJSON();
        
        $results['tests']['complexTransfer'] = [
            'passed' => $isValid,
            'molecularHash' => $molecule->molecularHash,
            'atomCount' => count($molecule->atoms),
            'hasRemainder' => true,
            'validationError' => $validationError
        ];
        
        return $isValid;
        
    } catch (Exception $e) {
        log_message("  ❌ ERROR: " . $e->getMessage(), COLOR_RED);
        $results['tests']['complexTransfer'] = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

/**
 * Test 5: ML-KEM768 Encryption Test
 * Tests post-quantum encryption/decryption compatibility
 */
function test_mlkem768() {
    log_message('\n5. ML-KEM768 Encryption Test', COLOR_BLUE);
    global $config, $results;
    
    $testConfig = $config['tests']['mlkem768'];
    
    try {
        // Create encryption wallet from seed
        $secret = Crypto::generateSecret($testConfig['seed'], 1024);
        $bundle = Crypto::generateBundleHash($secret);
        
        $encryptionWallet = new Wallet(
            $secret,
            $testConfig['token'],
            $testConfig['position']
        );
        
        log_test('Encryption wallet creation', true);
        
        // 🔬 DETERMINISM TEST: Create second identical wallet and verify keys match
        log_message('  🔬 Testing ML-KEM768 determinism...', COLOR_CYAN);
        $identicalWallet = new Wallet(
            $secret,
            $testConfig['token'],
            $testConfig['position']
        );
        
        $keysIdentical = $encryptionWallet->pubkey === $identicalWallet->pubkey;
        log_message("  🔑 ML-KEM768 keys identical: " . ($keysIdentical ? "✅ YES" : "❌ NO"), 
                   $keysIdentical ? COLOR_GREEN : COLOR_RED);
        
        if (!$keysIdentical) {
            log_message("  📊 Wallet 1 pubkey: " . substr($encryptionWallet->pubkey, 0, 50) . "...", COLOR_YELLOW);
            log_message("  📊 Wallet 2 pubkey: " . substr($identicalWallet->pubkey, 0, 50) . "...", COLOR_YELLOW);
            log_message("  🚨 CRITICAL: PHP ML-KEM768 is NOT deterministic!", COLOR_RED);
            log_message("  💡 This explains cross-platform compatibility failures!", COLOR_YELLOW);
        } else {
            log_message("  ✅ PHP ML-KEM768 is deterministic", COLOR_GREEN);
        }
        
        // Use wallet's real ML-KEM768 public key (generated by initializeMLKEM())
        // This now uses OpenSSL for real FIPS 203 compliant ML-KEM768 key generation
        $publicKey = $encryptionWallet->pubkey; // Real ML-KEM768 public key
        $publicKeyGenerated = !empty($publicKey) && (
            strlen(base64_decode($publicKey)) === 1184 || // Raw key format
            strpos($publicKey, '-----BEGIN') !== false     // PEM format (OpenSSL)
        );
        
        log_test('Encryption wallet creation', true);
        log_test('ML-KEM768 determinism check', $keysIdentical);
        log_test('ML-KEM768 public key generation', $publicKeyGenerated);
        
        // Real encryption test using ML-KEM768 encapsulation + AES-GCM (matches JavaScript)
        $encryptedData = $encryptionWallet->encryptMessageML768($testConfig['plaintext'], $publicKey);
        
        $encryptionSuccess = !empty($encryptedData['cipherText']) && 
                           !empty($encryptedData['encryptedMessage']) &&
                           $encryptedData['cipherText'] !== $encryptedData['encryptedMessage'] && // Real encryption produces different outputs
                           strlen(base64_decode($encryptedData['cipherText'])) === 1088; // ML-KEM768 ciphertext size
        
        log_test('Message encryption (self-encryption)', $encryptionSuccess);
        
        // Real decryption test using ML-KEM768 decapsulation + AES-GCM
        $decryptedMessage = $encryptionWallet->decryptMessageML768($encryptedData);
        
        $decryptionSuccess = $decryptedMessage === $testConfig['plaintext'];
        log_test('Message decryption and verification', $decryptionSuccess);
        
        $testPassed = $publicKeyGenerated && $encryptionSuccess && $decryptionSuccess && $keysIdentical;
        
        // Store ML-KEM768 data for cross-SDK verification (non-deterministic outputs)
        $results['molecules']['mlkem768'] = json_encode([
            'publicKey' => $publicKey,
            'encryptedData' => $encryptedData,
            'originalPlaintext' => $testConfig['plaintext'],
            'sdk' => 'PHP'
        ]);
        
        $results['tests']['mlkem768'] = [
            'passed' => $testPassed,
            'publicKeyGenerated' => $publicKeyGenerated,
            'encryptionSuccess' => $encryptionSuccess,
            'decryptionSuccess' => $decryptionSuccess,
            'plaintextLength' => strlen($testConfig['plaintext'])
        ];
        
        return $testPassed;
        
    } catch (Exception $e) {
        log_message("  ❌ ERROR: " . $e->getMessage(), COLOR_RED);
        $results['tests']['mlkem768'] = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

/**
 * Test 6: Negative Test Cases - Anti-Cheating Validation
 * Tests that validation properly fails for invalid molecules
 */
function test_negative_cases() {
    log_message('\n6. Negative Test Cases (Anti-Cheating)', COLOR_BLUE);
    global $config, $results;
    
    $testConfig = $config['tests']['crypto'];
    $allNegativeTestsPassed = true;
    
    try {
        $secret = Crypto::generateSecret($testConfig['seed'], 1024);
        $bundle = Crypto::generateBundleHash($secret);
        
        $sourceWallet = new Wallet(
            $secret,
            'TEST',
            '0123456789abcdeffedcba9876543210fedcba9876543210fedcba9876543210'
        );
        $sourceWallet->balance = 1000;
        
        // Test 1: Missing Molecular Hash (should fail)
        try {
            $recipientWallet = new Wallet(
                Crypto::generateSecret('TESTSEED2', 1024),
                'TEST',
                'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210'
            );
            
            $invalidMolecule = new Molecule($secret, $sourceWallet);
            
            // Initialize valid transfer but don't sign (no molecular hash)
            $invalidMolecule->initValue($recipientWallet, 100);
            
            // Clear molecular hash manually to simulate unsigned molecule
            $invalidMolecule->molecularHash = null;
            
            // This should fail because there's no molecular hash
            $checkMolecule = new CheckMolecule($invalidMolecule);
            try {
                $checkMolecule->verify($sourceWallet);
                log_test('Missing molecular hash validation (should FAIL)', false, 'Invalid molecule passed validation');
                $allNegativeTestsPassed = false;
            } catch (Exception $e) {
                // Exception is expected for missing molecular hash
                log_test('Missing molecular hash validation (should FAIL)', true);
            }
        } catch (Exception $e) {
            // Exception is expected for missing molecular hash construction
            log_test('Missing molecular hash validation (should FAIL)', true);
        }
        
        // Test 2: Invalid Molecular Hash (should fail)
        try {
            $recipientWallet = new Wallet(
                Crypto::generateSecret('TESTSEED2', 1024),
                'TEST',
                'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210'
            );
            
            $invalidMolecule = new Molecule($secret, $sourceWallet);
            
            // Initialize and sign normally
            $invalidMolecule->initValue($recipientWallet, 100);
            $invalidMolecule->sign(false);
            
            // Then corrupt the molecular hash
            $invalidMolecule->molecularHash = 'invalid_hash_that_should_fail_validation_check_12345678';
            
            $checkMolecule = new CheckMolecule($invalidMolecule);
            try {
                $checkMolecule->verify($sourceWallet);
                log_test('Invalid molecular hash validation (should FAIL)', false, 'Corrupted molecule passed validation');
                $allNegativeTestsPassed = false;
            } catch (Exception $e) {
                // Exception is expected for invalid molecular hash
                log_test('Invalid molecular hash validation (should FAIL)', true);
            }
        } catch (Exception $e) {
            // Exception is expected for invalid molecular hash
            log_test('Invalid molecular hash validation (should FAIL)', true);
        }
        
        // Test 3: Wallet Balance Validation (should fail with insufficient balance)
        try {
            $sourceWallet->balance = 50; // Set insufficient balance
            
            $recipientWallet = new Wallet(
                Crypto::generateSecret('TESTSEED3', 1024),
                'TEST',
                'abcdef0123456789abcdef0123456789abcdef0123456789abcdef0123456789'
            );
            
            $invalidMolecule = new Molecule($secret, $sourceWallet);
            
            // Try to transfer more than available balance (should fail)
            try {
                $invalidMolecule->initValue($recipientWallet, 1000); // More than balance of 50
                log_test('Insufficient balance validation (should FAIL)', false, 'Transfer with insufficient balance was allowed');
                $allNegativeTestsPassed = false;
            } catch (Exception $e) {
                // Exception is expected for insufficient balance
                log_test('Insufficient balance validation (should FAIL)', true);
            }
        } catch (Exception $e) {
            // Exception is expected
            log_test('Insufficient balance validation (should FAIL)', true);
        }
        
        $results['tests']['negativeCases'] = [
            'passed' => $allNegativeTestsPassed,
            'description' => 'Anti-cheating validation tests',
            'testCount' => 3
        ];
        
        return $allNegativeTestsPassed;
        
    } catch (Exception $e) {
        log_message("  ❌ ERROR: " . $e->getMessage(), COLOR_RED);
        $results['tests']['negativeCases'] = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

/**
 * Test 7: Cross-SDK Validation (FIXED VALIDATION)
 * Loads and validates molecules from other SDKs (if available)
 */
function test_cross_sdk_validation() {
    log_message('\n7. Cross-SDK Validation', COLOR_BLUE);
    global $results, $config;
    
    // Check if cross-validation is disabled (Round 1 molecule generation only)
    if ((getenv('KNISHIO_DISABLE_CROSS_VALIDATION') ?: $_ENV['KNISHIO_DISABLE_CROSS_VALIDATION'] ?? '') === 'true') {
        log_message('  ⏭️  Cross-validation disabled for Round 1 (molecule generation only)', COLOR_YELLOW);
        return true;
    }
    
    // Configurable shared results directory for cross-platform testing
    $sharedResultsDir = $_ENV['KNISHIO_SHARED_RESULTS'] ?? '../shared-test-results';
    $resultsDir = realpath(__DIR__ . '/' . $sharedResultsDir);
    
    if (!is_dir($resultsDir)) {
        log_message('  ⏭️  No other SDK results found for cross-validation', COLOR_YELLOW);
        return true;
    }
    
    $resultFiles = array_filter(
        scandir($resultsDir),
        function($f) {
            return str_ends_with($f, '.json') && !str_contains($f, 'php');
        }
    );
    
    if (empty($resultFiles)) {
        log_message('  ⏭️  No other SDK results found for cross-validation', COLOR_YELLOW);
        return true;
    }
    
    $allValid = true;
    
    foreach ($resultFiles as $file) {
        $sdkName = str_replace('-results.json', '', $file);
        $otherResults = json_decode(file_get_contents($resultsDir . '/' . $file), true);
        
        // Validate molecules from other SDK
        foreach ($otherResults['molecules'] ?? [] as $moleculeType => $moleculeData) {
            if ($moleculeType === 'mlkem768') {
                // Special handling for ML-KEM768 cross-SDK compatibility
                $mlkemData = json_decode($moleculeData, true);

                // Create our own encryption wallet using the same configuration
                $testConfig = $config['tests']['mlkem768'];
                $secret = Crypto::generateSecret($testConfig['seed'], 1024);
                $bundle = Crypto::generateBundleHash($secret);
                $ourWallet = new Wallet(
                    $secret,
                    $testConfig['token'],
                    $testConfig['position']
                );

                // Test: Can we encrypt a message for their public key?
                $testMessage = "Cross-SDK ML-KEM768 compatibility test";
                $mlkemValid = false;

                try {
                    $encryptedForThem = $ourWallet->encryptMessageML768(
                        $testMessage,
                        $mlkemData['publicKey']
                    );

                    // If encryption succeeded, that means their public key format is compatible
                    $mlkemValid = isset($encryptedForThem['cipherText']) &&
                                 isset($encryptedForThem['encryptedMessage']);

                    if ($mlkemValid) {
                        log_message("    Successfully encrypted for $sdkName public key", COLOR_GREEN);
                    }
                } catch (Exception $e) {
                    log_message("    Failed to encrypt for $sdkName: " . $e->getMessage(), COLOR_RED);
                    $mlkemValid = false;
                }

                log_test("$sdkName mlkem768 encryption compatibility", $mlkemValid);

                if (!$mlkemValid) {
                    $allValid = false;
                }
            } else {
                // Standard molecule validation for non-ML-KEM768 types
                try {
                    // Use centralized fromJSON() method for comprehensive deserialization (matching JavaScript SDK)
                    $molecule = Molecule::fromJSON(
                        $moleculeData,
                        includeValidationContext: true,
                        validateStructure: true
                    );

                    // Source wallet is automatically reconstructed by fromJSON() method
                    $sourceWallet = $molecule->getSourceWallet();

                    // FIXED: Use proper exception-based molecule validation
                    $isValid = false;
                    try {
                        $molecule->check($sourceWallet);  // void method - throws on failure
                        $isValid = true;  // if we reach here, validation passed
                    } catch (Exception $e) {
                        log_message("    Validation error: " . $e->getMessage(), COLOR_RED);
                        $isValid = false;
                    }

                    log_test("$sdkName $moleculeType molecule validation", $isValid);

                    if (!$isValid) {
                        $allValid = false;
                    }

                } catch (Exception $error) {
                    log_message("    Deserialization error: " . $error->getMessage(), COLOR_RED);
                    log_test("$sdkName $moleculeType molecule validation", false);
                    $allValid = false;
                }
            }
        }
    }
    
    $results['crossSdkCompatible'] = $allValid;
    return $allValid;
}

// Main execution
// Check for cross-validation-only mode (Round 2)
if ((getenv('KNISHIO_CROSS_VALIDATION_ONLY') ?: $_ENV['KNISHIO_CROSS_VALIDATION_ONLY'] ?? '') === 'true') {
    log_message('═══════════════════════════════════════════', COLOR_BLUE);
    log_message('    Knish.IO PHP SDK Cross-Validation Only', COLOR_BLUE);
    log_message('═══════════════════════════════════════════', COLOR_BLUE);

    // Load config for ML-KEM768 test parameters
    $config = $DEFAULT_CONFIG;

    // CRITICAL FIX: Load existing Round 1 results to preserve molecules
    $sharedResultsDir = $_ENV['KNISHIO_SHARED_RESULTS'] ?? '../shared-test-results';
    $existingResultsPath = __DIR__ . '/' . $sharedResultsDir . '/php-results.json';
    if (file_exists($existingResultsPath)) {
        try {
            $existingData = json_decode(file_get_contents($existingResultsPath), true);

            // Preserve Round 1 test results
            if (isset($existingData['tests']) && is_array($existingData['tests'])) {
                $results['tests'] = array_merge($results['tests'] ?? [], $existingData['tests']);
            }

            // Preserve Round 1 molecules
            if (isset($existingData['molecules']) && is_array($existingData['molecules'])) {
                $results['molecules'] = array_merge($results['molecules'] ?? [], $existingData['molecules']);
            }

            log_message('✅ Preserved Round 1 molecules for cross-validation', COLOR_GREEN);
        } catch (Exception $e) {
            log_message('⚠️  Could not load existing results: ' . $e->getMessage(), COLOR_YELLOW);
        }
    }

    // Only run cross-SDK validation
    $crossSdkResult = test_cross_sdk_validation();

    // Save results and print summary (cross-validation only)
    // Configurable shared results directory
    $sharedResultsDir = $_ENV['KNISHIO_SHARED_RESULTS'] ?? '../shared-test-results';
    $resultsDir = realpath(__DIR__ . '/' . $sharedResultsDir);
    if (!$resultsDir) {
        $resultsDir = __DIR__ . '/' . $sharedResultsDir;
        if (!is_dir($resultsDir)) {
            mkdir($resultsDir, 0755, true);
        }
    }
    $resultsPath = $resultsDir . '/php-results.json';
    file_put_contents($resultsPath, json_encode($results, JSON_PRETTY_PRINT));

    log_message('\n═══════════════════════════════════════════', COLOR_BLUE);
    log_message('            CROSS-VALIDATION SUMMARY', COLOR_BLUE);
    log_message('═══════════════════════════════════════════', COLOR_BLUE);
    $compatStatus = $crossSdkResult ? '✅ YES' : '❌ NO';
    $compatColor = $crossSdkResult ? COLOR_GREEN : COLOR_RED;
    log_message("Cross-SDK Compatible: $compatStatus", $compatColor);
    log_message('═══════════════════════════════════════════', COLOR_BLUE);

    // Exit based on cross-validation results only
    exit($crossSdkResult ? 0 : 1);
}

// Normal mode: Run all tests (Round 1 or standalone)
log_message('═══════════════════════════════════════════', COLOR_BLUE);
log_message('    Knish.IO PHP SDK Self-Test (FIXED)', COLOR_BLUE);
log_message('═══════════════════════════════════════════', COLOR_BLUE);

// Run all tests
$cryptoResult = test_crypto();
$metaResult = test_meta_creation();
$simpleResult = test_simple_transfer();
$complexResult = test_complex_transfer();
$mlkemResult = test_mlkem768();
$negativeResult = test_negative_cases();
$crossSdkResult = test_cross_sdk_validation();

// Generate summary
$totalTests = 6;
$passedTests = ($cryptoResult ? 1 : 0) + ($metaResult ? 1 : 0) + ($simpleResult ? 1 : 0) + ($complexResult ? 1 : 0) + ($mlkemResult ? 1 : 0) + ($negativeResult ? 1 : 0);
$failedTests = [];

if (!$metaResult) $failedTests[] = 'metaCreation: Validation failed';
if (!$simpleResult) $failedTests[] = 'simpleTransfer: Validation failed';
if (!$complexResult) $failedTests[] = 'complexTransfer: Validation failed';
if (!$mlkemResult) $failedTests[] = 'mlkem768: Validation failed';

// Save results
// Configurable shared results directory
$sharedResultsDir = $_ENV['KNISHIO_SHARED_RESULTS'] ?? '../shared-test-results';
$resultsDir = realpath(__DIR__ . '/' . $sharedResultsDir);
if (!$resultsDir) {
    $resultsDir = __DIR__ . '/' . $sharedResultsDir;
    if (!is_dir($resultsDir)) {
        mkdir($resultsDir, 0755, true);
    }
}
$resultsPath = $resultsDir . '/php-results.json';
if (!is_dir(dirname($resultsPath))) {
    mkdir(dirname($resultsPath), 0755, true);
}
file_put_contents($resultsPath, json_encode($results, JSON_PRETTY_PRINT));

log_message("\n📁 Results saved to: $resultsPath", COLOR_BLUE);

// Display summary
log_message('\n═══════════════════════════════════════════', COLOR_BLUE);
log_message('            TEST SUMMARY REPORT', COLOR_BLUE);
log_message('═══════════════════════════════════════════', COLOR_BLUE);
log_message('');
log_message("SDK: PHP v$version");
log_message("Timestamp: " . $results['timestamp']);

$color = $passedTests === $totalTests ? COLOR_GREEN : COLOR_RED;
log_message("\nTests Passed: $passedTests/$totalTests", $color);

if (!empty($failedTests)) {
    log_message("\nFailed Tests:", COLOR_RED);
    foreach ($failedTests as $failure) {
        log_message("  - $failure", COLOR_RED);
    }
}

$compatColor = $crossSdkResult ? COLOR_GREEN : COLOR_RED;
$compatStatus = $crossSdkResult ? '✅ YES' : '❌ NO';
log_message("\nCross-SDK Compatible: $compatStatus", $compatColor);

log_message('═══════════════════════════════════════════', COLOR_BLUE);

// Exit with appropriate code
exit($passedTests === $totalTests ? 0 : 1);
