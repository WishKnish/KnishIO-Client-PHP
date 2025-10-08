<?php

/**
 * Knish.IO PHP SDK Integration Test Script
 *
 * This script performs integration tests against a live Knish.IO validator node
 * using molecular-level operations. Since this matches the server language,
 * it provides the most accurate validation of server-client compatibility.
 * 
 * Usage:
 *   php integration-test.php --url https://testnet.knish.io/graphql
 *   KNISHIO_API_URL=https://localhost:8000/graphql php integration-test.php
 *   composer run integration-test http://localhost:8000/graphql
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;

// CLI argument parsing
$options = getopt('u:c:h', ['url:', 'cell:', 'timeout:', 'help']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP

Knish.IO PHP SDK Integration Test

Usage:
  php integration-test.php --url <graphql-url> [options]

Options:
  -u, --url <url>       GraphQL API URL (required)
  -c, --cell <slug>     Cell slug for testing
  --timeout <ms>        Request timeout in seconds (default: 30)
  -h, --help           Show this help message

Environment Variables:
  KNISHIO_API_URL      GraphQL API URL (alternative to --url)
  KNISHIO_CELL_SLUG    Cell slug (alternative to --cell)

Examples:
  php integration-test.php --url https://testnet.knish.io/graphql
  php integration-test.php --url http://localhost:8000/graphql
  
HELP;
    exit(0);
}

$graphqlUrl = $options['u'] ?? $options['url'] ?? 
              $argv[1] ?? 
              $_ENV['KNISHIO_API_URL'] ?? null;

if (!$graphqlUrl) {
    fwrite(STDERR, "❌ Error: GraphQL API URL is required\n");
    exit(1);
}

$config = [
    'server' => [
        'graphqlUrl' => $graphqlUrl,
        'cellSlug' => $options['c'] ?? $options['cell'] ?? $_ENV['KNISHIO_CELL_SLUG'] ?? 'PHP_INTEGRATION_TEST',
        'timeout' => (int)($options['timeout'] ?? 30)
    ],
    'tests' => [
        'authentication' => [
            'testSecret' => Crypto::generateSecret('PHP_INTEGRATION_AUTH')
        ],
        'metadata' => [
            'metaType' => 'PhpIntegrationTest',
            'metaId' => 'PHP_' . time() . '_' . bin2hex(random_bytes(4)),
            'metadata' => [
                'test_name' => 'PHP SDK Integration Test',
                'timestamp' => (new DateTime())->format('c'),
                'language' => 'PHP',
                'version' => PHP_VERSION,
                'server_match' => 'true',
                'description' => 'Server-client language match validation'
            ]
        ]
    ]
];

$results = [
    'sdk' => 'PHP',
    'testType' => 'Molecular-Level Integration',
    'version' => '1.0.0',
    'timestamp' => (new DateTime())->format('c'),
    'server' => [
        'url' => $config['server']['graphqlUrl'],
        'cellSlug' => $config['server']['cellSlug'],
        'architecture' => 'Molecule-centric'
    ],
    'tests' => [],
    'language' => 'PHP',
    'phpVersion' => PHP_VERSION,
    'overallSuccess' => false
];

// HTTP client for GraphQL requests
$httpClient = new HttpClient([
    'timeout' => $config['server']['timeout'],
    'headers' => [
        'Content-Type' => 'application/json',
        'User-Agent' => 'KnishIO-PHP-SDK/1.0.0 Integration-Test'
    ]
]);

// Color output functions
function colorLog(string $message, string $color = 'reset', int $indent = 0): void {
    $colors = [
        'reset' => "\033[0m",
        'bright' => "\033[1m",
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'cyan' => "\033[36m",
        'gray' => "\033[90m"
    ];
    
    $spaces = str_repeat('  ', $indent);
    echo "{$spaces}{$colors[$color]}{$message}{$colors['reset']}\n";
}

function logTest(string $testName, bool $passed, ?string $errorDetail = null, ?int $responseTime = null): void {
    $status = $passed ? '✅ PASS' : '❌ FAIL';
    $color = $passed ? 'green' : 'red';
    $timeStr = $responseTime !== null ? " ({$responseTime}ms)" : '';
    
    colorLog("{$status}: {$testName}{$timeStr}", $color, 1);
    
    if (!$passed && $errorDetail !== null) {
        colorLog($errorDetail, 'red', 2);
    }
}

function logSection(string $sectionName): void {
    colorLog("\n{$sectionName}", 'blue');
    colorLog(str_repeat('═', strlen($sectionName) + 4), 'blue');
}

/**
 * Execute GraphQL request with proper error handling
 */
function executeGraphQLRequest(string $query, array $variables = []): array {
    global $httpClient, $config;
    
    try {
        $response = $httpClient->post($config['server']['graphqlUrl'], [
            'json' => [
                'query' => $query,
                'variables' => $variables
            ]
        ]);
        
        $body = (string)$response->getBody();
        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        
        if (isset($data['errors'])) {
            $errorMessages = array_column($data['errors'], 'message');
            throw new Exception('GraphQL Error: ' . implode(', ', $errorMessages));
        }
        
        return $data['data'] ?? [];
        
    } catch (RequestException $e) {
        throw new Exception("HTTP Error: {$e->getMessage()}");
    } catch (JsonException $e) {
        throw new Exception("JSON Error: {$e->getMessage()}");
    }
}

/**
 * Test 1: PHP Server Connectivity and Schema Validation
 */
function testServerConnectivity(): bool {
    global $results;
    
    logSection('1. PHP Server Connectivity and Schema Validation');
    
    $testResults = [];
    
    try {
        $startTime = microtime(true);
        
        // Test schema introspection with PHP
        $schemaData = executeGraphQLRequest('
            query {
                __schema {
                    queryType { name }
                    mutationType { name }
                }
            }
        ');
        
        $responseTime = (int)((microtime(true) - $startTime) * 1000);
        
        $hasValidSchema = ($schemaData['__schema']['queryType']['name'] ?? '') === 'Query' &&
                         ($schemaData['__schema']['mutationType']['name'] ?? '') === 'Mutation';
        
        logTest('PHP GraphQL schema introspection', $hasValidSchema, 
            $hasValidSchema ? null : 'Invalid GraphQL schema structure', $responseTime);
        
        // Test ProposeMolecule availability
        $mutationsData = executeGraphQLRequest('
            query {
                __type(name: "Mutation") {
                    fields { name }
                }
            }
        ');
        
        $mutations = array_column($mutationsData['__type']['fields'] ?? [], 'name');
        $hasProposeMolecule = in_array('ProposeMolecule', $mutations, true);
        
        logTest('ProposeMolecule mutation availability (PHP)', $hasProposeMolecule,
            $hasProposeMolecule ? null : 'ProposeMolecule not found');
        
        colorLog('Available mutations: ' . implode(', ', $mutations), 'gray', 2);
        
        $testResults = [
            'passed' => $hasValidSchema && $hasProposeMolecule,
            'responseTime' => $responseTime,
            'availableMutations' => $mutations,
            'language' => 'PHP',
            'phpVersion' => PHP_VERSION
        ];
        
    } catch (Exception $e) {
        logTest('PHP server connectivity', false, $e->getMessage());
        $testResults = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
    }
    
    $results['tests']['connectivity'] = $testResults;
    return $testResults['passed'] ?? false;
}

/**
 * Test 2: PHP Authentication Token with Native Language Match
 */
function testPhpAuthenticationToken(): bool {
    global $results, $config;
    
    logSection('2. PHP Authentication Token (Server Language Match)');
    
    $testResults = [];
    
    try {
        $testSecret = $config['tests']['authentication']['testSecret'];
        
        // Create PHP auth wallet
        $authWallet = new Wallet($testSecret, 'AUTH');
        
        logTest('PHP auth wallet creation', true);
        
        $startTime = microtime(true);
        
        // Request access token (PHP to PHP server)
        $tokenData = executeGraphQLRequest('
            mutation RequestToken($cellSlug: String, $pubkey: String, $encrypt: Boolean) {
                AccessToken(cellSlug: $cellSlug, pubkey: $pubkey, encrypt: $encrypt) {
                    token
                    expiresAt
                }
            }
        ', [
            'cellSlug' => $config['server']['cellSlug'],
            'pubkey' => $authWallet->pubkey ?? 'php-test-pubkey',
            'encrypt' => false
        ]);
        
        $responseTime = (int)((microtime(true) - $startTime) * 1000);
        
        $token = $tokenData['AccessToken']['token'] ?? null;
        $tokenSuccess = !empty($token);
        
        logTest('PHP access token generation', $tokenSuccess,
            $tokenSuccess ? null : 'Failed to generate token', $responseTime);
        
        if ($tokenSuccess) {
            colorLog('PHP auth token: ' . substr($token, 0, 20) . '...', 'gray', 2);
        }
        
        $testResults = [
            'passed' => $tokenSuccess,
            'responseTime' => $responseTime,
            'tokenLength' => strlen($token ?? ''),
            'expiresAt' => $tokenData['AccessToken']['expiresAt'] ?? null,
            'language' => 'PHP',
            'serverLanguageMatch' => true
        ];
        
    } catch (Exception $e) {
        logTest('PHP authentication token', false, $e->getMessage());
        $testResults = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
    }
    
    $results['tests']['authentication'] = $testResults;
    return $testResults['passed'] ?? false;
}

/**
 * Test 3: PHP Molecular Metadata Creation (Native Server Match)
 */
function testPhpMolecularMetadataCreation(): bool {
    global $results, $config;
    
    logSection('3. PHP Molecular Metadata Creation (Language Match)');
    
    $testResults = [];
    
    try {
        $testSecret = $config['tests']['authentication']['testSecret'];
        $testBundle = Crypto::generateBundleHash($testSecret);
        $testConfig = $config['tests']['metadata'];
        
        // Create PHP source wallet
        $sourceWallet = new Wallet(
            $testSecret, 
            'USER', 
            '0123456789abcdeffedcba9876543210fedcba9876543210fedcba9876543210'
        );
        
        logTest('PHP source wallet creation', true);
        
        // Create PHP molecule
        $molecule = new Molecule($testSecret, $sourceWallet, null, $config['server']['cellSlug']);
        
        // Convert metadata to PHP format
        $metaArray = [];
        foreach ($testConfig['metadata'] as $key => $value) {
            $metaArray[] = ['key' => $key, 'value' => (string)$value];
        }
        
        // Initialize metadata molecule using PHP SDK (correct parameter order: meta array first)
        $molecule->initMeta($metaArray, $testConfig['metaType'], $testConfig['metaId']);
        
        logTest('PHP metadata molecule initialization', true);
        
        // Sign molecule with PHP cryptography
        $molecule->sign();
        logTest('PHP molecule signing', true);
        
        $startTime = microtime(true);
        
        // Convert PHP molecule to GraphQL format
        $moleculeInput = [
            'molecularHash' => $molecule->molecularHash,
            'cellSlug' => $config['server']['cellSlug'],
            'bundle' => $testBundle,
            'status' => $molecule->status ?? 'pending',
            'createdAt' => $molecule->createdAt,
            'atoms' => array_map(function($atom) {
                return [
                    'position' => $atom->position,
                    'walletAddress' => $atom->walletAddress,
                    'isotope' => $atom->isotope,
                    'token' => $atom->token,
                    'value' => $atom->value,
                    'batchId' => $atom->batchId,
                    'metaType' => $atom->metaType,
                    'metaId' => $atom->metaId,
                    'meta' => $atom->meta ?? [],
                    'otsFragment' => $atom->otsFragment,
                    'index' => $atom->index
                ];
            }, $molecule->atoms)
        ];
        
        // Submit molecule via ProposeMolecule (PHP to PHP server)
        $moleculeData = executeGraphQLRequest('
            mutation ProposeMolecule($molecule: MoleculeInput!) {
                ProposeMolecule(molecule: $molecule) {
                    molecularHash
                    status
                    createdAt
                }
            }
        ', ['molecule' => $moleculeInput]);
        
        $responseTime = (int)((microtime(true) - $startTime) * 1000);
        
        $serverMolecularHash = $moleculeData['ProposeMolecule']['molecularHash'] ?? null;
        $submissionSuccess = !empty($serverMolecularHash);
        
        logTest('PHP molecule submission via ProposeMolecule', $submissionSuccess,
            $submissionSuccess ? null : 'PHP molecule submission failed', $responseTime);
        
        // Verify molecular hash consistency (PHP validation)
        $hashMatches = $serverMolecularHash === $molecule->molecularHash;
        logTest('PHP molecular hash verification', $hashMatches,
            $hashMatches ? null : "Hash mismatch: expected {$molecule->molecularHash}, got {$serverMolecularHash}");
        
        $testResults = [
            'passed' => $submissionSuccess && $hashMatches,
            'responseTime' => $responseTime,
            'clientMolecularHash' => $molecule->molecularHash,
            'serverMolecularHash' => $serverMolecularHash,
            'atomCount' => count($molecule->atoms),
            'hashMatches' => $hashMatches,
            'language' => 'PHP',
            'serverLanguageMatch' => true
        ];
        
    } catch (Exception $e) {
        logTest('PHP molecular metadata creation', false, $e->getMessage());
        $testResults = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
    }
    
    $results['tests']['molecularMetadata'] = $testResults;
    return $testResults['passed'] ?? false;
}

/**
 * Test 4: PHP Query Validation (Server Language Match)
 */
function testPhpQueryValidation(): bool {
    global $results, $config;
    
    logSection('4. PHP Query Validation (Language Match)');
    
    $testResults = [];
    
    try {
        $testSecret = $config['tests']['authentication']['testSecret'];
        $testBundle = Crypto::generateBundleHash($testSecret);
        
        $startTime = microtime(true);
        
        // Test ContinuId query (more appropriate for fresh server)
        $continuIdData = executeGraphQLRequest('
            query TestContinuId($bundle: String!) {
                ContinuId(bundle: $bundle) {
                    position
                    token
                }
            }
        ', [
            'bundle' => $testBundle
        ]);
        
        $responseTime = (int)((microtime(true) - $startTime) * 1000);
        
        $querySuccess = isset($continuIdData['ContinuId']);
        
        logTest('PHP ContinuId query execution', $querySuccess,
            $querySuccess ? null : 'ContinuId query failed', $responseTime);
        
        if ($querySuccess) {
            colorLog('PHP ContinuId result: ' . json_encode($continuIdData['ContinuId']), 'gray', 2);
        }
        
        $testResults = [
            'passed' => $querySuccess,
            'responseTime' => $responseTime,
            'queryType' => 'ContinuId',
            'hasResult' => $querySuccess,
            'language' => 'PHP',
            'serverLanguageMatch' => true
        ];
        
    } catch (Exception $e) {
        logTest('PHP query validation', false, $e->getMessage());
        $testResults = [
            'passed' => false,
            'error' => $e->getMessage()
        ];
    }
    
    $results['tests']['queryValidation'] = $testResults;
    return $testResults['passed'] ?? false;
}

/**
 * Save PHP integration test results
 */
function saveResults(): void {
    global $results;
    
    $resultsDir = $_ENV['KNISHIO_SHARED_RESULTS'] ?? 
                  __DIR__ . '/../shared-test-results';
    
    if (!is_dir($resultsDir)) {
        mkdir($resultsDir, 0755, true);
    }
    
    $resultsFile = $resultsDir . '/php-integration-results.json';
    file_put_contents($resultsFile, json_encode($results, JSON_PRETTY_PRINT));
    
    colorLog("\n📁 Results saved to: {$resultsFile}", 'blue');
}

/**
 * Print comprehensive summary with PHP-specific metrics
 */
function printSummary(): void {
    global $results;
    
    logSection('PHP INTEGRATION TEST SUMMARY');
    
    $tests = $results['tests'];
    $totalTests = count($tests);
    $passedTests = count(array_filter($tests, fn($test) => $test['passed'] ?? false));
    
    colorLog("\nSDK: {$results['sdk']} v{$results['version']}", 'bright');
    colorLog("Language: PHP {$results['phpVersion']} (Server Match)", 'bright');
    colorLog("Server: {$results['server']['url']}", 'bright');
    colorLog("Architecture: {$results['server']['architecture']}", 'bright');
    
    colorLog("\nTests Passed: {$passedTests}/{$totalTests}", 
        $passedTests === $totalTests ? 'green' : 'red');
    
    if ($passedTests < $totalTests) {
        colorLog("\nFailed Tests:", 'red');
        foreach ($tests as $testName => $testResult) {
            if (!($testResult['passed'] ?? false)) {
                $error = $testResult['error'] ?? 'Test failed';
                colorLog("  - {$testName}: {$error}", 'red', 1);
            }
        }
    }
    
    colorLog("\n" . str_repeat('═', 60), 'blue');
}

/**
 * Main PHP integration test runner
 */
function runPhpIntegrationTests(): void {
    global $results, $config;
    
    colorLog(str_repeat('═', 70), 'blue');
    colorLog('  Knish.IO PHP SDK - Molecular Integration Tests', 'bright');
    colorLog(str_repeat('═', 70), 'blue');
    
    colorLog("\n🌐 Server: {$config['server']['graphqlUrl']}", 'cyan');
    colorLog("📱 Cell: {$config['server']['cellSlug']}", 'cyan');
    colorLog("🔧 Language: PHP {$results['phpVersion']} (Server Match)", 'cyan');
    colorLog("⚛️  Architecture: Molecule-centric (ProposeMolecule)", 'cyan');
    
    $startTime = microtime(true);
    
    try {
        // Test 1: Server Connectivity
        $connectivitySuccess = testServerConnectivity();
        
        if (!$connectivitySuccess) {
            colorLog("\n❌ Cannot continue without proper server connectivity", 'red');
            $results['overallSuccess'] = false;
            return;
        }
        
        // Test 2: PHP Authentication Token
        $authSuccess = testPhpAuthenticationToken();
        
        // Test 3: PHP Molecular Metadata Creation
        $molecularSuccess = testPhpMolecularMetadataCreation();
        
        // Test 4: PHP Query Validation
        $querySuccess = testPhpQueryValidation();
        
        // Calculate final results
        $allTestsPassed = array_reduce(
            $results['tests'], 
            fn($carry, $test) => $carry && ($test['passed'] ?? false), 
            true
        );
        $results['overallSuccess'] = $allTestsPassed;
        
    } catch (Exception $e) {
        colorLog("\n❌ Fatal Error: {$e->getMessage()}", 'red');
        $results['overallSuccess'] = false;
        $results['fatalError'] = $e->getMessage();
    }
    
    $totalTime = (int)((microtime(true) - $startTime) * 1000);
    $results['totalExecutionTime'] = $totalTime;
    
    // Save results and print summary
    saveResults();
    printSummary();
    
    colorLog("\n⏱️  Total execution time: {$totalTime}ms", 'gray');
    colorLog("🔧 PHP Advantage: Native server language compatibility", 'gray');
    
    // Exit with appropriate code
    $exitCode = $results['overallSuccess'] ? 0 : 1;
    $status = $results['overallSuccess'] ? 'PASSED' : 'FAILED';
    $color = $results['overallSuccess'] ? 'green' : 'red';
    
    colorLog("\n" . ($results['overallSuccess'] ? '✅' : '❌') . " PHP Integration tests {$status}", $color);
    
    exit($exitCode);
}

// Handle process termination
function handleShutdown(): void {
    global $results;
    
    colorLog("\n🛑 PHP integration tests interrupted", 'yellow');
    $results['overallSuccess'] = false;
    $results['interrupted'] = true;
    saveResults();
    exit(1);
}

register_shutdown_function('handleShutdown');

// Run PHP integration tests
try {
    runPhpIntegrationTests();
} catch (Throwable $e) {
    fwrite(STDERR, "\n❌ Unhandled PHP Error: {$e->getMessage()}\n");
    exit(1);
}