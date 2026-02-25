<?php
/*
                               (
                              (/(
                              (//(
                              (///(
                             (/////(
                             (//////(                          )
                            (////////(                        (/)
                            (////////(                       (///)
                           (//////////(                      (////)
                           (//////////(                     (//////)
                          (////////////(                    (///////)
                         (/////////////(                   (/////////)
                        (//////////////(                  (///////////)
                        (///////////////(                (/////////////)
                       (////////////////(               (//////////////)
                      (((((((((((((((((((              (((((((((((((((
                     (((((((((((((((((((              ((((((((((((((
                     (((((((((((((((((((            ((((((((((((((
                    ((((((((((((((((((((           (((((((((((((
                    ((((((((((((((((((((          ((((((((((((
                    (((((((((((((((((((         ((((((((((((
                    (((((((((((((((((((        ((((((((((
                    ((((((((((((((((((/      (((((((((
                    ((((((((((((((((((     ((((((((
                    (((((((((((((((((    (((((((
                   ((((((((((((((((((  (((((
                   #################  ##
                   ################  #
                  ################# ##
                 %################  ###
                 ###############(   ####
                ###############      ####
               ###############       ######
              %#############(        (#######
             %#############           #########
            ############(              ##########
           ###########                  #############
          #########                      ##############
        %######

        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Query\QueryBalance;
use WishKnish\KnishIO\Client\Query\QueryAtom;
use WishKnish\KnishIO\Client\Query\QueryBatchHistory;
use WishKnish\KnishIO\Client\Query\QueryPolicy;
use WishKnish\KnishIO\Client\Mutation\MutationCreateToken;
use WishKnish\KnishIO\Client\Mutation\MutationCreateMeta;
use WishKnish\KnishIO\Client\Mutation\MutationCreateRule;
use WishKnish\KnishIO\Client\Mutation\MutationLinkIdentifier;

/**
 * Test suite for KnishIOClient
 * @package WishKnish\KnishIO\Client\Tests
 */
class KnishIOClientTest extends TestCase {

  private ?KnishIOClient $client = null;
  private string $testUri = 'http://localhost:8080/graphql';
  private string $testSecret;

  protected function setUp(): void {
    parent::setUp();
    
    // Initialize test client
    $this->client = new KnishIOClient($this->testUri);
    
    // Generate a test secret
    $this->testSecret = Crypto::generateSecret();
    $this->client->setSecret($this->testSecret);
  }

  protected function tearDown(): void {
    $this->client = null;
    parent::tearDown();
  }

  /**
   * Test client initialization
   */
  public function testClientInitialization(): void {
    $this->assertInstanceOf(KnishIOClient::class, $this->client);
    $this->assertEquals($this->testUri, $this->client->uri());
    $this->assertTrue($this->client->hasSecret());
  }

  /**
   * Test multiple URI initialization
   */
  public function testMultipleUriInitialization(): void {
    $uris = [
      'http://node1.example.com/graphql',
      'http://node2.example.com/graphql',
      'http://node3.example.com/graphql'
    ];
    
    $client = new KnishIOClient($uris);
    $randomUri = $client->getRandomUri();
    
    $this->assertContains($randomUri, $uris);
  }

  /**
   * Test secret and bundle management
   */
  public function testSecretAndBundleManagement(): void {
    // Test secret
    $secret = Crypto::generateSecret();
    $this->client->setSecret($secret);
    
    $this->assertEquals($secret, $this->client->getSecret());
    $this->assertTrue($this->client->hasSecret());
    
    // Test bundle
    $bundle = $this->client->getBundle();
    $this->assertNotEmpty($bundle);
    $this->assertTrue($this->client->hasBundle());
    
    // Test reset
    $this->client->reset();
    $this->assertFalse($this->client->hasSecret());
    $this->assertFalse($this->client->hasBundle());
  }

  /**
   * Test server SDK version
   */
  public function testServerSdkVersion(): void {
    $version = 3;
    $client = new KnishIOClient($this->testUri, null, $version);
    
    $this->assertEquals($version, $client->getServerSdkVersion());
  }

  /**
   * Test encryption switching
   */
  public function testEncryptionSwitching(): void {
    // Initially not encrypted
    $result = $this->client->switchEncryption(false);
    $this->assertFalse($result); // No change
    
    // Switch to encrypted
    $result = $this->client->switchEncryption(true);
    $this->assertTrue($result); // Changed
    
    // Try to switch again to same state
    $result = $this->client->switchEncryption(true);
    $this->assertFalse($result); // No change
  }

  /**
   * Test molecule creation
   */
  public function testMoleculeCreation(): void {
    $molecule = $this->client->createMolecule();
    
    $this->assertInstanceOf(Molecule::class, $molecule);
    $this->assertNotEmpty($molecule->molecularHash);
    $this->assertEquals('KnishIO', $molecule->cellSlug);
  }

  /**
   * Test wallet creation
   */
  public function testWalletCreation(): void {
    $secret = Crypto::generateSecret();
    $wallet = new Wallet($secret, 'TEST');
    
    $this->assertNotEmpty($wallet->address);
    $this->assertNotEmpty($wallet->position);
    $this->assertNotEmpty($wallet->bundleHash);
    $this->assertEquals('TEST', $wallet->tokenSlug);
  }

  /**
   * Test query creation
   */
  public function testQueryCreation(): void {
    $query = $this->client->createQuery(QueryBalance::class);
    
    $this->assertInstanceOf(QueryBalance::class, $query);
  }

  /**
   * Test mutation creation
   */
  public function testMutationCreation(): void {
    $mutation = $this->client->createMoleculeMutation(MutationCreateToken::class);
    
    $this->assertInstanceOf(MutationCreateToken::class, $mutation);
    $this->assertInstanceOf(Molecule::class, $mutation->molecule());
  }

  /**
   * Test new query methods exist
   */
  public function testNewQueryMethodsExist(): void {
    // Test queryAtom method exists
    $this->assertTrue(method_exists($this->client, 'queryAtom'));
    
    // Test queryBatchHistory method exists
    $this->assertTrue(method_exists($this->client, 'queryBatchHistory'));
    
    // Test queryPolicy method exists
    $this->assertTrue(method_exists($this->client, 'queryPolicy'));
  }

  /**
   * Test new mutation methods exist
   */
  public function testNewMutationMethodsExist(): void {
    // Test createRule method exists
    $this->assertTrue(method_exists($this->client, 'createRule'));
    
    // Test linkIdentifier method exists
    $this->assertTrue(method_exists($this->client, 'linkIdentifier'));
  }

  /**
   * Test fingerprint methods
   */
  public function testFingerprintMethods(): void {
    // Test getFingerprint returns a hash
    $fingerprint = $this->client->getFingerprint();
    $this->assertIsString($fingerprint);
    $this->assertEquals(64, strlen($fingerprint)); // SHA256 produces 64 character hex
    
    // Test getFingerprintData returns array
    $data = $this->client->getFingerprintData();
    $this->assertIsArray($data);
    $this->assertArrayHasKey('userAgent', $data);
    $this->assertArrayHasKey('acceptLanguage', $data);
  }

  /**
   * Test QueryAtom variables creation
   */
  public function testQueryAtomVariables(): void {
    $params = [
      'molecularHash' => 'hash123',
      'bundleHash' => 'bundle456',
      'tokenSlug' => 'TEST',
      'isotope' => 'V',
      'latest' => true
    ];
    
    $variables = QueryAtom::createVariables($params);
    
    $this->assertArrayHasKey('molecularHashes', $variables);
    $this->assertContains('hash123', $variables['molecularHashes']);
    $this->assertArrayHasKey('bundleHashes', $variables);
    $this->assertContains('bundle456', $variables['bundleHashes']);
    $this->assertArrayHasKey('tokenSlugs', $variables);
    $this->assertContains('TEST', $variables['tokenSlugs']);
    $this->assertArrayHasKey('isotopes', $variables);
    $this->assertContains('V', $variables['isotopes']);
    $this->assertTrue($variables['latest']);
  }

  /**
   * Test cell slug management
   */
  public function testCellSlugManagement(): void {
    $cellSlug = 'TestCell';
    $this->client->setCellSlug($cellSlug);
    
    $molecule = $this->client->createMolecule();
    $this->assertEquals($cellSlug, $molecule->cellSlug);
  }

  /**
   * Test source and remainder wallet retrieval
   */
  public function testWalletRetrieval(): void {
    $sourceWallet = $this->client->getSourceWallet();
    $this->assertNull($sourceWallet); // Should be null initially
    
    $remainderWallet = $this->client->getRemainderWallet();
    $this->assertNull($remainderWallet); // Should be null initially
  }
}