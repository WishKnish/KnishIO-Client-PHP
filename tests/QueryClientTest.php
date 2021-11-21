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

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use JetBrains\PhpStorm\NoReturn;
use ReflectionException;
use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Mutation\MutationCreatePeer;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Tests\QueryServerTest;

// !!! @todo: this unit test must to be separated from any server side (it should work as an independent part) !!!

/**
 * Class QueryClientTest
 * @package WishKnish\KnishIO\Client\Tests
 */
class QueryClientTest extends TestCase {
  protected string $source_secret;
  protected Wallet $source_wallet;

  protected ?HttpClient $guzzle_client;

  /**
   * @throws Exception
   */
  public function beforeExecute (): void {
    parent::beforeExecute();

    // Source secret & wallet
    $this->source_secret = Crypto::generateSecret();
    $this->source_wallet = new Wallet ( $this->source_secret );

    // Guzzle client from the KnishIOClient object
    $this->guzzle_client = $this->client( $this->source_secret )
        ->client();
  }

  /**
   * Clear data test
   *
   * @throws ReflectionException
   * @throws Exception
   */
  public function testClearAll (): void {

    // Call server cleanup
    $this->callServerCleanup( QueryServerTest::class );

    // Initial code
    $this->beforeExecute();

    // Default assertion
    $this->assertEquals( true, true );
  }

  /**
   * @throws GuzzleException
   * @throws Exception
   */
  public function testMetaIsotope (): void {

    // Call server cleanup
    $this->callServerCleanup( QueryServerTest::class );

    $this->beforeExecute();

    // Create a meta molecule
    $molecule = $this->client( $this->source_secret )
        ->createMolecule();
    $molecule->initMeta( [ 'key1' => 'value1', 'key2' => 'value2' ], 'metaType', 'metaId' );
    $molecule->sign();
    $molecule->check();

    // Execute query & check response
    $this->executeMolecule( $this->source_secret, $molecule );
  }

  /**
   * @throws ReflectionException|GuzzleException
   * @throws Exception
   */
  public function testMetaWalletBundle (): void {

    $this->assertEquals( true, true );
    return;

    $this->beforeExecute();

    // Meta & encryption
    $meta = [ 'key1' => 'value1', 'key2' => 'value2' ];

    $server_secret = env( 'SECRET_TOKEN_KNISH' );
    $server_wallet = $this->client( $server_secret )
        ->queryContinuId( Crypto::generateBundleHash( $server_secret ) )
        ->payload();

    /*
    $server_wallet = new Wallet( $server_secret, 'USER', 'f0d565b50fd40bda4afd128f4daafe77bd6c8561dc3ab5422ecca5e5726054c4');

    dump ($server_wallet->position);
    $value = [
      '6D10LZNmlLs' => 'AGG5pXiVQgnUXsrWopHrOaJENY4DGvQ270NenAAL3LZCW9MELVRSeHZ2aaR7YEhg5lDKvUUF8hqFHubv8CIgb8EMMkqf0ZI7G9Pe2sB3HiUudDa',
      '6m6r0SckeEB' => 'BM1g2kMOvHCUngJcMKK9KFlKPfCTmU9CSgAlJtEGf4Td5cabTOdPGM9lp9o2Ujbgs6pjVYgHHqJTRt4llBhiof036rHWjL4JdcdjlpCTkTAhndt',
    ];
    $result = $server_wallet->decryptMyMessage ($value);
    dd ($result);
    */

    // Create a meta molecule
    $molecule = $this->client( $this->source_secret )
        ->createMolecule();
    $molecule->initBundleMeta( $molecule->encryptMessage( $meta, [ $server_wallet ] ) );
    $molecule->sign();
    $molecule->check();

    // Execute query & check response
    $this->executeMolecule( $this->source_secret, $molecule );
  }

  public function testAppendMetaIsotope (): void {
    /*
    $this->beforeExecute();

    // Create a meta molecule
    $molecule = $this->client($this->source_secret)->createMolecule();
    $molecule->initMetaAppend(
      ['key2' => 'value2', 'key3' => 'value3'],
      'metaType',
      'metaId'
    );
    $molecule->sign();
    $molecule->check();

    // Execute query & check response
    $this->executeMolecule( $this->source_secret, $molecule );
    */
  }

  /**
   * @throws GuzzleException
   * @throws Exception
   */
  public function testWalletCreation (): void {

    $this->beforeExecute();

    // New wallet
    $new_wallet_secret = Crypto::generateSecret();
    $newWallet = new Wallet( $new_wallet_secret, 'UTINITWALLET' );

    // Create a molecule
    $molecule = $this->client( $this->source_secret )
        ->createMolecule();
    $molecule->initWalletCreation( $newWallet );
    $molecule->sign();

    // Execute query & check response
    $this->executeMolecule( $this->source_secret, $molecule );
  }

  /**
   * @throws Exception
   * @throws GuzzleException
   */
  #[NoReturn]
  public function testPeerCreation (): void {

    $this->beforeExecute();

    /**
     * @var MutationCreatePeer $query
     */
    $query = $this->client( $this->source_secret )
        ->createMoleculeMutation( MutationCreatePeer::class );
    $query->fillMolecule( 'testPeerSlug', 'test.peer', 'testPeerName', [ 'cellslug1', 'cellslug2' ] );

    $molecule = $query->execute();

    dd( $molecule );
  }

}
