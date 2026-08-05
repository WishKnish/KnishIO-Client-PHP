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

/**
 * Resolve the configured KNISHIO_SHARED_RESULTS directory to a filesystem path,
 * WITHOUT calling realpath()/creating it (callers add that as they already do).
 *
 * Two bugs fixed here (found while wiring up the bufferFamily gate — cycle
 * bufferFamilyPhpPython):
 *  1. `$_ENV['KNISHIO_SHARED_RESULTS']` alone is a no-op on a stock php.ini: $_ENV is
 *     only populated from the process environment when `variables_order` includes
 *     "E", which is NOT the PHP default (default is "GPCS") — getenv() must be
 *     checked first, matching the pattern already used for
 *     KNISHIO_DISABLE_CROSS_VALIDATION / KNISHIO_CROSS_VALIDATION_ONLY below.
 *  2. `__DIR__ . '/' . $sharedResultsDir` silently NESTS an absolute
 *     KNISHIO_SHARED_RESULTS value under this script's directory instead of using
 *     it as-is (string concatenation doesn't "reset to root" the way path-join
 *     helpers do) — detect an absolute path and use it directly.
 */
function resolveSharedResultsBase(): string {
    $configured = getenv('KNISHIO_SHARED_RESULTS') ?: ($_ENV['KNISHIO_SHARED_RESULTS'] ?? '../shared-test-results');
    $isAbsolute = str_starts_with($configured, '/') || preg_match('#^[A-Za-z]:[\\/]#', $configured) === 1;
    return $isAbsolute ? $configured : (__DIR__ . '/' . $configured);
}

// Embedded test configuration for SDK self-containment (PHP best practices)
$DEFAULT_CONFIG = [
    'tests' => [
        'crypto' => [
            'seed' => 'TESTSEED',
            'secret' => 'e8ffc86d60fc6a73234a834166e7436e21df6c3209dfacc8d0bd6595707872c3799abbf7deee0f9c4b58de1fd89b9abb67a207558208d5ccf550c227d197c24e9fcc3707aeb53c4031d38392020ff72bcaa0f728aa8bc3d47d95ff0afc04d8fcdb69bff638ce56646c154fc92aa517d3c40f550d2ccacbd921724e1d94b82aed2c8e172a8a7ed5a6963f5890157fe77222b97af3787741f9d3cec0b40aec6f07ae4b2b24614f0a20e035aee0df04e176175dc100eb1b00dd7ea95c28cdec47958336945333c3bef24719ed949fa56d1541f24c725d4f374a533bf255cf22f4596147bcd1ba05abcecbe9b12095e1fdddb094616894c366498be0b5785c180100efb3c5b689fc1c01131633fe1775df52a970e9472ab7bc0c19f5742b9e9436753cd16024b2d326b763eca68c414755a0d2fdbb927f007e9413f1190578b2033a03d29387f5aea71b07a5ce80fbfd45be4a15440faadeac50e41846022894fc683a52328b470bc1860c8b038d7258f504178918502b93d84d8b0fbef3e02f89f83cb1ff033a2bdbdf2a2ba78d80c12aa8b2d6c10d76c468186bd4a4e9eacc758546bb50ed7b1ee241cc5b93ff924c7bbee6778b27789e1f9104c917fc93f735eee5b25c07a883788f3d2e0771e751c4f59b76f8426027ac2b07a2ca84534433d0a1b86cef3288e7d79e8b175a3955848cfd1dfbdcd6b5bafcf6789e56e8ef40af09a764147640eb10b426349f6ffc8e299cdcebffc3a9d6be362ba33fbf648bf06ea4c35890c705df479030fd1d0669d289dcbabaaf78f945c37fc69f3823dbfa99bdf3cf7bb7be8f810a7eab5167e26691642c3982aa203687d0e674154c970cfc1822f9917f2100ae8950cf0fcab074bfb578f4f6e78df490f0fd9becdba7151f2a5733cc2a3df845aa17bdc49765163d635de5c3a1c376683e622fe3e0a6092a35dfedc4bc5bc9c120d2ed06d899775bcd16417318f4b5c7ba27fdc0a442884a69e71543a13cb26762a0df4f47807924a15da7895b6c96accb09394fdf0232d922a99f4a9f95d46da7b9050eb661f3329fe98372175a82d5e5296e4a31c040da6407194251b5baa7338071d1edfc51f55ca409ffd885045e47412f97a4bbe2e73794d8b276ccb446843bbc38c7e580dc4dc2ba94556de0d80681f60d1b2953021e08a60e26685adf61eff91d9ca7daa04a72de9dc2822655648f3c0f5016967b0e8104d70add65b9b9ce98b3aaa10106f5f32133775a71ab9b006307e390b697c77bb828c3ad07bfdcc3ecf3149ac98dc8a230c281365719d67fd2450c717ad1391880d9c17cb8ba96b6254ac783aeae04f84f14829e4efc6ee73b77670cb9ea96dc73e5464bc4cf46cdd2ebe75009d9c4ce6097eab2858ef2899b3dcd147c579939f45c4ad2aa283b6e9c8ca2539abd5e2332cff851f4fa8c4767732d7977',
            'bundle' => '2b77ff69a6d2f8108250389377faa6cbd42caaefa2f966e1b68a4b3fc022c83e',
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
        ],
        'tokenCreation' => [
            'sourceSeed' => 'TESTSEED',
            'recipientSeed' => 'RECIPIENTSEED',
            'amount' => 1000000,
            'sourceToken' => 'USER',
            'newToken' => 'TESTTOKEN',
            'sourcePosition' => '0123456789abcdeffedcba9876543210fedcba9876543210fedcba9876543210',
            'recipientPosition' => 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210',
            'meta' => ['name' => 'Test Token', 'fungibility' => 'fungible', 'supply' => 'limited', 'decimals' => '0']
        ],
        'walletCreation' => [
            'sourceSeed' => 'TESTSEED',
            'newWalletSeed' => 'NEWWALLETSEED',
            'sourceToken' => 'USER',
            'newToken' => 'TESTTOKEN',
            'sourcePosition' => '0123456789abcdeffedcba9876543210fedcba9876543210fedcba9876543210',
            'newWalletPosition' => 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210'
        ],
        'shadowWalletClaim' => [
            'sourceSeed' => 'TESTSEED',
            'claimSeed' => 'CLAIMSEED',
            'sourceToken' => 'USER',
            'claimToken' => 'TESTTOKEN',
            'sourcePosition' => '0123456789abcdeffedcba9876543210fedcba9876543210fedcba9876543210',
            'claimPosition' => 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210'
        ]
    ]
];

// Support optional external config override via environment variable
$configPath = getenv('KNISHIO_TEST_CONFIG') ?: ($_ENV['KNISHIO_TEST_CONFIG'] ?? null);
if ($configPath && file_exists($configPath)) {
    $config = json_decode(file_get_contents($configPath), true);
} else {
    $config = $DEFAULT_CONFIG;
}

// Get version from composer.json. Fail loudly rather than fabricate a version: a
// silent '1.0.0' fallback here would misreport every results file as an unversioned,
// never-released SDK (the same class of bug the Python self-test had — see SDK_VERSION
// there) and would defeat the gauntlet's version-consistency gate instead of tripping it.
$composerPath = __DIR__ . '/composer.json';
if (!file_exists($composerPath)) {
    fwrite(STDERR, "FATAL: composer.json not found at $composerPath - cannot determine SDK version.\n");
    exit(1);
}
$composerData = json_decode(file_get_contents($composerPath), true);
if (!is_array($composerData) || empty($composerData['version'])) {
    fwrite(STDERR, "FATAL: composer.json is missing a \"version\" field - refusing to fabricate one.\n");
    exit(1);
}
$version = $composerData['version'];

// Test results storage (matches JavaScript SDK format)
$results = [
    'sdk' => 'PHP',
    'version' => $version,
    'timestamp' => date('c'),
    // Run identity. shared-test-results/ holds one mutable file per SDK with no record of
    // which run wrote it, so a later standalone run silently replaces the evidence an
    // already-published report was built from.
    'runId' => (getenv('KNISHIO_RUN_ID') ?: null),
    'tests' => [],
    'molecules' => [],
    // Starts false. This was true, making "fully cross-SDK compatible" the default state
    // before a single peer molecule had been examined — so every early return out of
    // testCrossSdkValidation published a pass. A verdict must be earned; the safe default
    // for a check that has not run is "failed".
    'crossSdkCompatible' => false,
    // Coverage behind the verdict: the boolean alone cannot distinguish "validated seven
    // peers, all passed" from "validated nothing and so found no failures".
    'crossValidation' => ['ran' => false, 'targetsExpected' => 0, 'targetsValidated' => 0],
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
        $secret = Crypto::generateSecret($testConfig['seed'], 2048);
        log_message("  Generated secret length: " . strlen($secret), COLOR_YELLOW);
        log_message("  First 64 chars: " . substr($secret, 0, 64) . "...", COLOR_YELLOW);
        log_message("  Expected length: 2048", COLOR_YELLOW);
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
        $sourceSecret = Crypto::generateSecret($testConfig['seed'], 2048);
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
        $sourceSecret = Crypto::generateSecret($testConfig['sourceSeed'], 2048);
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
        $recipientSecret = Crypto::generateSecret($testConfig['recipientSeed'], 2048);
        
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
        $sourceSecret = Crypto::generateSecret($testConfig['sourceSeed'], 2048);
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
        $recipientSecret = Crypto::generateSecret($testConfig['recipient1Seed'], 2048);
        
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
 * Test C1: Token Creation Test (cross-SDK parity with JS)
 * C-atom (issue new token) + ContinuID I-atom; the prefixed setMetaWallet class
 */
function test_token_creation() {
    log_message('\nC1. Token Creation Test', COLOR_BLUE);
    global $config, $results;

    $testConfig = $config['tests']['tokenCreation'];

    try {
        // Create source wallet (USER token)
        $sourceSecret = Crypto::generateSecret($testConfig['sourceSeed'], 2048);

        $sourceWallet = new Wallet(
            $sourceSecret,
            $testConfig['sourceToken'],
            $testConfig['sourcePosition']
        );
        log_test('Source wallet creation', true);

        // Create recipient wallet for the new token
        $recipientSecret = Crypto::generateSecret($testConfig['recipientSeed'], 2048);
        $recipientWallet = new Wallet(
            $recipientSecret,
            $testConfig['newToken'],
            $testConfig['recipientPosition']
        );
        log_test('Recipient wallet creation', true);

        // USER-token remainder so addContinuIdAtom's guard keeps the canonical bbbb... wallet
        $remainderWallet = createFixedRemainderWallet($sourceSecret, $testConfig['sourceToken']);
        log_test('Remainder wallet creation', true);

        $molecule = new Molecule($sourceSecret, $sourceWallet, $remainderWallet);
        $molecule->initTokenCreation($recipientWallet, $testConfig['amount'], $testConfig['meta']);
        log_test('Token creation initialization', true);

        setFixedTimestamps($molecule);
        $molecule->sign(false);
        log_test('Molecule signing', true);

        inspect_molecule($molecule, 'token creation molecule');

        $isValid = false;
        $validationError = null;
        try {
            $checkMolecule = new CheckMolecule($molecule);
            $checkMolecule->verify($sourceWallet);  // void method - throws on failure
            $isValid = true;
        } catch (Exception $error) {
            $isValid = false;
            $validationError = $error->getMessage();
        }
        log_test('Molecule validation', $isValid, $validationError);

        $results['molecules']['tokenCreation'] = $molecule->toJSON();
        $results['tests']['tokenCreation'] = [
            'passed' => $isValid,
            'molecularHash' => $molecule->molecularHash,
            'atomCount' => count($molecule->atoms),
            'validationError' => $validationError
        ];

        return $isValid;

    } catch (Exception $e) {
        log_message("  ❌ ERROR: " . $e->getMessage(), COLOR_RED);
        $results['tests']['tokenCreation'] = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

/**
 * Test C2: Wallet Creation Test (cross-SDK parity with JS)
 * C-atom (new wallet) + ContinuID I-atom; the prefixed setMetaWallet class
 */
function test_wallet_creation() {
    log_message('\nC2. Wallet Creation Test', COLOR_BLUE);
    global $config, $results;

    $testConfig = $config['tests']['walletCreation'];

    try {
        $sourceSecret = Crypto::generateSecret($testConfig['sourceSeed'], 2048);
        $sourceWallet = new Wallet(
            $sourceSecret,
            $testConfig['sourceToken'],
            $testConfig['sourcePosition']
        );
        log_test('Source wallet creation', true);

        $newWallet = new Wallet(
            Crypto::generateSecret($testConfig['newWalletSeed'], 2048),
            $testConfig['newToken'],
            $testConfig['newWalletPosition']
        );
        log_test('New wallet creation', true);

        $remainderWallet = createFixedRemainderWallet($sourceSecret, $testConfig['sourceToken']);
        log_test('Remainder wallet creation', true);

        $molecule = new Molecule($sourceSecret, $sourceWallet, $remainderWallet);
        $molecule->initWalletCreation($newWallet);
        log_test('Wallet creation initialization', true);

        setFixedTimestamps($molecule);
        $molecule->sign(false);
        log_test('Molecule signing', true);

        inspect_molecule($molecule, 'wallet creation molecule');

        $isValid = false;
        $validationError = null;
        try {
            $checkMolecule = new CheckMolecule($molecule);
            $checkMolecule->verify($sourceWallet);  // void method - throws on failure
            $isValid = true;
        } catch (Exception $error) {
            $isValid = false;
            $validationError = $error->getMessage();
        }
        log_test('Molecule validation', $isValid, $validationError);

        $results['molecules']['walletCreation'] = $molecule->toJSON();
        $results['tests']['walletCreation'] = [
            'passed' => $isValid,
            'molecularHash' => $molecule->molecularHash,
            'atomCount' => count($molecule->atoms),
            'validationError' => $validationError
        ];

        return $isValid;

    } catch (Exception $e) {
        log_message("  ❌ ERROR: " . $e->getMessage(), COLOR_RED);
        $results['tests']['walletCreation'] = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

/**
 * Test C3: Shadow Wallet Claim Test (cross-SDK parity with JS)
 * C-atom (shadowWalletClaim + setMetaWallet) + ContinuID I-atom
 */
function test_shadow_wallet_claim() {
    log_message('\nC3. Shadow Wallet Claim Test', COLOR_BLUE);
    global $config, $results;

    $testConfig = $config['tests']['shadowWalletClaim'];

    try {
        $sourceSecret = Crypto::generateSecret($testConfig['sourceSeed'], 2048);
        $sourceWallet = new Wallet(
            $sourceSecret,
            $testConfig['sourceToken'],
            $testConfig['sourcePosition']
        );
        log_test('Source wallet creation', true);

        $claimWallet = new Wallet(
            Crypto::generateSecret($testConfig['claimSeed'], 2048),
            $testConfig['claimToken'],
            $testConfig['claimPosition']
        );
        log_test('Claim wallet creation', true);

        $remainderWallet = createFixedRemainderWallet($sourceSecret, $testConfig['sourceToken']);
        log_test('Remainder wallet creation', true);

        $molecule = new Molecule($sourceSecret, $sourceWallet, $remainderWallet);
        $molecule->initShadowWalletClaim($claimWallet);
        log_test('Shadow wallet claim initialization', true);

        setFixedTimestamps($molecule);
        $molecule->sign(false);
        log_test('Molecule signing', true);

        inspect_molecule($molecule, 'shadow wallet claim molecule');

        $isValid = false;
        $validationError = null;
        try {
            $checkMolecule = new CheckMolecule($molecule);
            $checkMolecule->verify($sourceWallet);  // void method - throws on failure
            $isValid = true;
        } catch (Exception $error) {
            $isValid = false;
            $validationError = $error->getMessage();
        }
        log_test('Molecule validation', $isValid, $validationError);

        $results['molecules']['shadowWalletClaim'] = $molecule->toJSON();
        $results['tests']['shadowWalletClaim'] = [
            'passed' => $isValid,
            'molecularHash' => $molecule->molecularHash,
            'atomCount' => count($molecule->atoms),
            'validationError' => $validationError
        ];

        return $isValid;

    } catch (Exception $e) {
        log_message("  ❌ ERROR: " . $e->getMessage(), COLOR_RED);
        $results['tests']['shadowWalletClaim'] = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
        return false;
    }
}

/**
 * Test B1: Buffer Family (B-isotope deposit + withdraw), vector-driven.
 *
 * Deposit shape:  V(-sourceBalance) -> B(+amount) -> V(+remainder)
 * Withdraw shape: B(-sourceBalance) -> V(+amount) -> B(+remainder)
 *
 * Both are cross-isotope conservation cases: the V and B atom values must sum to
 * zero together, which a V-only conservation check reads as unbalanced. Drives off
 * the canonical vectors so all eight SDKs assert against identical expectations.
 *
 * An absent fixture is recorded as an explicit `skipped`, never a silent pass — a
 * skip that reported as a pass to the exit-code gate and as a failure to the summary
 * is how this entire test family stayed invisible across the SDK fleet.
 */
function test_buffer_family() {
    log_message("\nB1. Buffer Family Test (deposit + withdraw, vector-driven)", COLOR_BLUE);
    global $results;

    $candidates = [];
    if ($env = getenv('KNISHIO_CANONICAL_VECTORS')) {
        $candidates[] = $env;
    }
    $candidates[] = __DIR__ . '/tests/fixtures/canonical-patent-vectors.json';
    $candidates[] = resolveSharedResultsBase() . '/canonical-patent-vectors.json';

    $vectorsPath = null;
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            $vectorsPath = $candidate;
            break;
        }
    }

    if ($vectorsPath === null) {
        // In an orchestrated cross-SDK run the vectors are mandatory: silently
        // skipping parity coverage is the false-green this gate exists to stop.
        $mustHave = getenv('KNISHIO_REQUIRE_VECTORS') === 'true';
        $results['tests']['bufferFamily'] = [
            'passed' => false,
            'skipped' => !$mustHave,
            'molecularHash' => null,
            'atomCount' => 0,
            'validationError' => 'canonical-patent-vectors.json absent',
        ];
        if ($mustHave) {
            log_message('  FAILED: canonical-patent-vectors.json absent (KNISHIO_REQUIRE_VECTORS=true)', COLOR_RED);
            return false;
        }
        log_message('  SKIPPED: canonical-patent-vectors.json absent (standalone CI)', COLOR_YELLOW);
        return true; // skip, not fail — recorded as skipped, never counted as a pass
    }

    try {
        $vectors = json_decode(file_get_contents($vectorsPath), true, 512, JSON_THROW_ON_ERROR)['vectors'];

        $secret = Crypto::generateSecret('buffer-family-self-test-seed', 2048);
        $token = 'BUFTOK';
        $allPass = true;
        $lastHash = null;
        $atomTotal = 0;

        // ---- DEPOSIT: V (source -balance) -> B (buffer +amount) -> V (remainder) ----
        foreach ($vectors['buffer_deposit_conservation']['tests'] as $tv) {
            $name = $tv['name'];

            $source = new Wallet($secret, $token);          // fresh source -> valid OTS key
            $source->balance = $tv['sourceBalance'];
            $remainder = new Wallet($secret, $token);

            $molecule = new Molecule($secret, $source, $remainder);
            $molecule->initDepositBuffer($tv['amount'], []);
            setFixedTimestamps($molecule);
            $molecule->sign(false);

            $sum = 0;
            foreach ($molecule->atoms as $atom) {
                if ($atom->isotope === 'V' || $atom->isotope === 'B') {
                    $sum += (int) $atom->value;
                }
            }

            $shape = count($molecule->atoms) === 3
                && $molecule->atoms[0]->isotope === 'V' && (string) $molecule->atoms[0]->value === $tv['expectedSourceValue']
                && $molecule->atoms[1]->isotope === 'B' && (string) $molecule->atoms[1]->value === $tv['expectedBufferValue']
                && $molecule->atoms[2]->isotope === 'V' && (string) $molecule->atoms[2]->value === $tv['expectedRemainderValue'];

            $verified = false;
            $verifyError = null;
            try {
                (new CheckMolecule($molecule))->verify($source);
                $verified = true;
            } catch (Exception $error) {
                $verifyError = $error->getMessage();
                log_message('    check() error: ' . $verifyError, COLOR_RED);
            }

            $ok = $shape && ((string) $sum === $tv['expectedSum']) && $verified;
            log_test("deposit $name conserves (V+B sum 0; cross-isotope bypass)", $ok, $verifyError);
            $allPass = $allPass && $ok;
            $lastHash = $molecule->molecularHash;
            $atomTotal += count($molecule->atoms);
        }

        // ---- WITHDRAW: B (source -balance) -> V (recipient +amount) -> B (remainder) ----
        foreach ($vectors['buffer_withdraw_conservation']['tests'] as $tv) {
            $name = $tv['name'];

            $source = new Wallet($secret, $token);          // the buffer wallet: B source AND remainder
            $source->balance = $tv['sourceBalance'];

            $molecule = new Molecule($secret, $source, $source);
            $molecule->initWithdrawBuffer([ $source->bundle => $tv['amount'] ], $source);
            setFixedTimestamps($molecule);
            $molecule->sign(false);

            $sum = 0;
            foreach ($molecule->atoms as $atom) {
                if ($atom->isotope === 'V' || $atom->isotope === 'B') {
                    $sum += (int) $atom->value;
                }
            }

            $shape = count($molecule->atoms) === 3
                && $molecule->atoms[0]->isotope === 'B' && (string) $molecule->atoms[0]->value === $tv['expectedSourceValue']
                && $molecule->atoms[1]->isotope === 'V' && (string) $molecule->atoms[1]->value === $tv['expectedRecipientValue']
                && $molecule->atoms[2]->isotope === 'B' && (string) $molecule->atoms[2]->value === $tv['expectedRemainderValue'];

            $verified = false;
            $verifyError = null;
            try {
                (new CheckMolecule($molecule))->verify($source);
                $verified = true;
            } catch (Exception $error) {
                $verifyError = $error->getMessage();
                log_message('    check() error: ' . $verifyError, COLOR_RED);
            }

            $ok = $shape && ((string) $sum === $tv['expectedSum']) && $verified;
            log_test("withdraw $name conserves (B+V sum 0; cross-isotope bypass)", $ok, $verifyError);
            $allPass = $allPass && $ok;
            $lastHash = $molecule->molecularHash;
            $atomTotal += count($molecule->atoms);
        }

        // ---- NEGATIVE: molecules that must be REJECTED (buffer_conservation_negative) ----
        // The two loops above only prove VALID molecules are ACCEPTED; that gap concealed a
        // live fail-open defect in Kotlin (V-only conservation gated on B/F presence with no
        // isotopeB/isotopeF to own it), so a molecule destroying value verified clean. Build a
        // VALID molecule with the SDK's own builder, apply the vector's single tamper mutation,
        // re-sign, and assert rejection. Missing key (older fixture) is a no-op, not a crash.
        foreach (($vectors['buffer_conservation_negative']['tests'] ?? []) as $tv) {
            $name = $tv['name'];

            $source = new Wallet($secret, $token);
            $source->balance = $tv['sourceBalance'];

            $buildFrom = $tv['buildFrom'];
            if ($buildFrom === 'deposit') {
                $remainder = new Wallet($secret, $token);
                $molecule = new Molecule($secret, $source, $remainder);
                $molecule->initDepositBuffer($tv['amount'], []);
            } elseif ($buildFrom === 'withdraw') {
                $molecule = new Molecule($secret, $source, $source);
                $molecule->initWithdrawBuffer([ $source->bundle => $tv['amount'] ], $source);
            } else {
                throw new Exception("unknown buildFrom '$buildFrom' in buffer_conservation_negative");
            }

            // Tamper target selects the first/last atom of that isotope in emission order.
            // addAtom() sorts by index after every append, and index is assigned in append
            // order, so $molecule->atoms is still in emission order here (pre-sign).
            $tamper = $tv['tamper'];
            $isotope = substr($tamper['target'], -1); // 'firstV'/'lastV' -> 'V'; 'firstB'/'lastB' -> 'B'
            $matches = array_values(array_filter(
                $molecule->atoms,
                static fn($atom) => $atom->isotope === $isotope
            ));
            $targetAtom = str_starts_with($tamper['target'], 'first') ? $matches[0] : $matches[count($matches) - 1];
            $field = $tamper['field'];
            $targetAtom->$field = $tamper['to'];

            setFixedTimestamps($molecule);
            $molecule->sign(false);

            $rejected = false;
            $rejectReason = null;
            try {
                (new CheckMolecule($molecule))->verify($source);
            } catch (Exception $error) {
                $rejected = true;
                $rejectReason = get_class($error) . ': ' . $error->getMessage();
            }

            log_test("$name rejected ({$tv['reason']})", $rejected, $rejected ? null : 'molecule was NOT rejected');
            if ($rejected) {
                log_message("    rejected via $rejectReason", COLOR_CYAN);
            }
            $allPass = $allPass && $rejected;
            $lastHash = $molecule->molecularHash;
            $atomTotal += count($molecule->atoms);
        }

        $results['tests']['bufferFamily'] = [
            'passed' => $allPass,
            'skipped' => false,
            'molecularHash' => $lastHash,
            'atomCount' => $atomTotal,
            'validationError' => $allPass ? null : 'buffer family vector validation failed',
        ];

        return $allPass;

    } catch (Exception $e) {
        log_message('  ❌ ERROR: ' . $e->getMessage(), COLOR_RED);
        $results['tests']['bufferFamily'] = [
            'passed' => false,
            'skipped' => false,
            'molecularHash' => null,
            'atomCount' => 0,
            'validationError' => $e->getMessage(),
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
        $secret = Crypto::generateSecret($testConfig['seed'], 2048);
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
        $secret = Crypto::generateSecret($testConfig['seed'], 2048);
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
                Crypto::generateSecret('TESTSEED2', 2048),
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
                Crypto::generateSecret('TESTSEED2', 2048),
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
                Crypto::generateSecret('TESTSEED3', 2048),
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
    
    global $results;

    // Round 1 generates molecules and does not cross-validate, so it holds no opinion here
    // and must not leave a verdict behind.
    if ((getenv('KNISHIO_DISABLE_CROSS_VALIDATION') ?: $_ENV['KNISHIO_DISABLE_CROSS_VALIDATION'] ?? '') === 'true') {
        log_message('  ⏭️  Cross-validation disabled for Round 1 (molecule generation only)', COLOR_YELLOW);
        $results['crossValidation'] = ['ran' => false, 'targetsExpected' => 0, 'targetsValidated' => 0];
        return true;
    }

    // Configurable shared results directory for cross-platform testing
    $resultsDir = realpath(resolveSharedResultsBase());
    $results['crossValidation']['ran'] = true;

    // A missing shared directory in Round 2 is a HARD FAILURE, not a skip. This returned
    // true — "compatible" — having found nothing to check. Absence of evidence must never
    // be reported as evidence of compatibility.
    if (!$resultsDir || !is_dir($resultsDir)) {
        log_message('  ❌ Shared results directory not found — cross-validation CANNOT run', COLOR_RED);
        $results['crossSdkCompatible'] = false;
        return false;
    }

    // Scope to *-results.json. `str_ends_with($f, '.json')` also matched the canonical
    // vector MASTERS that live in this directory (canonical-patent-vectors.json,
    // cross-platform-test-vectors.json) and fed them into the peer loop as SDK results.
    // They carry no 'molecules' key, so they inflated the apparent peer count while
    // contributing to neither pass nor fail.
    $resultFiles = array_filter(
        scandir($resultsDir),
        function($f) {
            return str_ends_with($f, '-results.json') && !str_contains($f, 'php');
        }
    );

    // Zero peers in Round 2 means Round 2 did not happen.
    if (empty($resultFiles)) {
        log_message('  ❌ No peer SDK results found — nothing to cross-validate', COLOR_RED);
        $results['crossSdkCompatible'] = false;
        return false;
    }

    // Canonical set mirrors requiredMoleculeKeys in sdks/canonical-test-keys.json.
    $requiredMoleculeTypes = [
        'metadata', 'simpleTransfer', 'complexTransfer', 'tokenCreation',
        'walletCreation', 'shadowWalletClaim', 'mlkem768',
    ];

    $results['crossValidation']['targetsExpected'] = count($resultFiles);
    $peersValidated = 0;
    $allValid = true;

    foreach ($resultFiles as $file) {
        $sdkName = str_replace('-results.json', '', $file);
        $otherResults = json_decode(file_get_contents($resultsDir . '/' . $file), true);

        // A peer must publish every molecule type before we can claim to have validated
        // it. The loop below iterates the keys that are PRESENT, so an omitted molecule is
        // indistinguishable from a validated one — which is how Kotlin's Round-2 drop of
        // tokenCreation/walletCreation/shadowWalletClaim passed every peer on 2026-07-27.
        $published = $otherResults['molecules'] ?? [];
        $absent = array_values(array_filter(
            $requiredMoleculeTypes,
            function ($t) use ($published) { return empty($published[$t]); }
        ));
        if (!empty($absent)) {
            log_message("    ❌ {$sdkName} published no molecule for: " . implode(', ', $absent), COLOR_RED);
            log_test("{$sdkName} publishes all required molecules", false);
            $allValid = false;
        }

        $peersValidated++;

        // Validate molecules from other SDK
        foreach ($published as $moleculeType => $moleculeData) {
            if ($moleculeType === 'mlkem768') {
                // Special handling for ML-KEM768 cross-SDK compatibility
                $mlkemData = json_decode($moleculeData, true);

                // Create our own encryption wallet using the same configuration
                $testConfig = $config['tests']['mlkem768'];
                $secret = Crypto::generateSecret($testConfig['seed'], 2048);
                $bundle = Crypto::generateBundleHash($secret);
                $ourWallet = new Wallet(
                    $secret,
                    $testConfig['token'],
                    $testConfig['position']
                );

                // STRONG cross-SDK check (cycle 138): decrypt THEIR encryptedData with our
                // TESTSEED wallet (all 8 SDKs share the keypair) and assert the plaintext —
                // real decrypt-interop, not just "their pubkey is encaps-usable" (the old weak form).
                $mlkemValid = false;

                try {
                    $decrypted = $ourWallet->decryptMessageML768($mlkemData['encryptedData']);
                    $mlkemValid = ($decrypted === ($mlkemData['originalPlaintext'] ?? null));

                    if ($mlkemValid) {
                        log_message("    Successfully decrypted $sdkName's ML-KEM768 message", COLOR_GREEN);
                    } else {
                        log_message("    Decrypted $sdkName plaintext mismatch", COLOR_RED);
                    }
                } catch (Exception $e) {
                    log_message("    Failed to decrypt $sdkName: " . $e->getMessage(), COLOR_RED);
                    $mlkemValid = false;
                }

                log_test("$sdkName mlkem768 decryption compatibility", $mlkemValid);

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
    
    // COVERAGE FLOOR. `$allValid` starts true and only becomes false on a DETECTED
    // failure, so it records "nothing went wrong", not "everything was checked". Those
    // differ whenever the loop examined fewer peers than it should have. Require both.
    $results['crossValidation']['targetsValidated'] = $peersValidated;
    $expected = $results['crossValidation']['targetsExpected'];

    $fullCoverage = ($peersValidated === $expected);
    if (!$fullCoverage) {
        log_message("  ❌ Incomplete coverage: validated {$peersValidated}/{$expected} peer SDKs", COLOR_RED);
    }
    log_message("  📊 Cross-validation coverage: {$peersValidated}/{$expected} peer SDKs", COLOR_BLUE);

    $results['crossSdkCompatible'] = $allValid && $fullCoverage;
    return $results['crossSdkCompatible'];
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
    $existingResultsPath = resolveSharedResultsBase() . '/php-results.json';
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
    $resultsDir = realpath(resolveSharedResultsBase());
    if (!$resultsDir) {
        $resultsDir = resolveSharedResultsBase();
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
$tokenCreationResult = test_token_creation();
$walletCreationResult = test_wallet_creation();
$shadowWalletClaimResult = test_shadow_wallet_claim();
$bufferFamilyResult = test_buffer_family();
$mlkemResult = test_mlkem768();
$negativeResult = test_negative_cases();
$crossSdkResult = test_cross_sdk_validation();

// Generate summary
// A skipped test is neither a pass nor a failure. Counting it as either is what let
// bufferFamily read as passing to the exit-code gate and failing to the summary.
$bufferSkipped = ($results['tests']['bufferFamily']['skipped'] ?? false) === true;

$totalTests = 10;
$passedTests = ($cryptoResult ? 1 : 0) + ($metaResult ? 1 : 0) + ($simpleResult ? 1 : 0) + ($complexResult ? 1 : 0) + ($tokenCreationResult ? 1 : 0) + ($walletCreationResult ? 1 : 0) + ($shadowWalletClaimResult ? 1 : 0) + (($bufferFamilyResult && !$bufferSkipped) ? 1 : 0) + ($mlkemResult ? 1 : 0) + ($negativeResult ? 1 : 0);
$skippedTests = $bufferSkipped ? 1 : 0;
$failedCount = $totalTests - $passedTests - $skippedTests;
$failedTests = [];

if (!$metaResult) $failedTests[] = 'metaCreation: Validation failed';
if (!$simpleResult) $failedTests[] = 'simpleTransfer: Validation failed';
if (!$complexResult) $failedTests[] = 'complexTransfer: Validation failed';
if (!$bufferFamilyResult && !$bufferSkipped) {
    $failedTests[] = 'bufferFamily: ' . ($results['tests']['bufferFamily']['validationError'] ?? 'Validation failed');
}
if (!$mlkemResult) $failedTests[] = 'mlkem768: Validation failed';

// Save results
// Configurable shared results directory
$resultsDir = realpath(resolveSharedResultsBase());
if (!$resultsDir) {
    $resultsDir = resolveSharedResultsBase();
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

$color = $failedCount === 0 ? COLOR_GREEN : COLOR_RED;
log_message("\nTests Passed: $passedTests/$totalTests", $color);
if ($skippedTests > 0) {
    log_message("Tests Skipped: $skippedTests/$totalTests", COLOR_YELLOW);
    if ($bufferSkipped) {
        log_message('  - bufferFamily: ' . ($results['tests']['bufferFamily']['validationError'] ?? 'skipped'));
    }
}

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

// Exit with appropriate code. A skip does not fail the run; a failure always does.
exit($failedCount === 0 ? 0 : 1);
