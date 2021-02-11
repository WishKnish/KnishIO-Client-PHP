<?php

namespace WishKnish\KnishIO\Client\Tests;

use http\Client;
use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Mutation\MutationTransferTokens;
use WishKnish\KnishIO\Client\Query\QueryBalance;
use WishKnish\KnishIO\Client\Tests\TestCase;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Client\Wallet as ClientWallet;
use WishKnish\KnishIO\Client\Query\QueryBatch;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
use WishKnish\KnishIO\Client\Query\QueryWalletList;


/**
 * Class TokenUnitTransactionTest
 * @package WishKnish\KnishIO\Client\Tests
 */
class TokenUnitTransactionTest extends TestCase
{
  private $serverTokenSlug = 'UTENVSTACKUNIT';
  private $tokenSlug = 'UTSTACKUNIT';
  private $transactionAmount = 2;
  private $cascadeDeep = 4;
  private $batchPrefix = 'batch_';

  private $tokenUnits = [
    [ 'unit_id_1', 'unit_name_1', 'unit_meta_1', ],
    [ 'unit_id_2', 'unit_name_2', 'unit_meta_2', ],
    [ 'unit_id_3', 'unit_name_3', 'unit_meta_3', ],
    [ 'unit_id_4', 'unit_name_4', 'unit_meta_4', ],
    [ 'unit_id_5', 'unit_name_5', 'unit_meta_5', ],
    [ 'unit_id_6', 'unit_name_6', 'unit_meta_6', ],
    [ 'unit_id_7', 'unit_name_7', 'unit_meta_7', ],
    [ 'unit_id_8', 'unit_name_8', 'unit_meta_8', ],
    [ 'unit_id_9', 'unit_name_9', 'unit_meta_9', ],
    [ 'unit_id_10','unit_name_10','unit_meta_10', ],
    [ 'unit_id_11','unit_name_11','unit_meta_11', ],
  ];


  /**
   * Clear data test
   *
   * @throws \ReflectionException
   */
  public function testClearAll () {

    // Initial code
    $this->beforeExecute();

    // Call server cleanup
    $this->callServerCleanup(\WishKnish\KnishIO\Tests\TokenServerTransactionTest::class);

    // Deafult assertion
    $this->assertEquals(true, true);
  }


  /**
   * @throws \ReflectionException
   */
  public function testUnitTransaction() {
    $this->beforeExecute();

    $secret = Crypto::generateSecret();

    // Create a token
    $client = $this->createToken( $this->tokenSlug, $this->tokenUnits, $secret );
    $transactionAmount = $this->transactionAmount;


    // Previously test the transfer errors
    $this->testUnitsErrorTransaction( $secret );

    
    // Transferring through cascade
    for ( $i = 0; $i < $this->cascadeDeep; $i++ ) {

      // Create batchID
      $index = $i + 1;
      $batchId = $this->getBatchId( $index );

      // Get token units part for a transaction
      $tokenUnits = array_slice( $this->tokenUnits, ($i + 1) * 2 );

      // Sending token unit IDs
      $sendingTokenUnitIds = $this->getTokenUnitIds( $tokenUnits );

      // Token transferring
      $client = $this->transfetToken( $client, $sendingTokenUnitIds, $batchId );

      // Claim created shadow wallet
      $this->claimShadowWallet( $client, $this->tokenSlug );

      // Create a meta to custom batchID
      $client->createMeta( 'batch', $batchId, [
        'key_shared' => 'value_shared',
        "key_$index" => "value_$index",
      ] );

      // Change transaction amount for each step
      $transactionAmount -= 10;

      // Burn tokens for the last transaction
      if ( $i === $this->cascadeDeep - 1 ) {
        for($j = 0; $j < 2; $j++ ) {
          $tokenUnits = array_slice( $this->tokenUnits, ($i + 1) * 2 + $j, 1 );
          $client->burnToken( $this->tokenSlug, $this->getTokenUnitIds( $tokenUnits ), $this->getBatchId( $index + $j + 1) );
        }
      }
    }

    // Get metas for last batchID
    $response = $client->queryBalance( $this->tokenSlug );
    $this->assertEquals( array_get( $response->payload()->tokenUnits, '0.id' ), array_get( $this->tokenUnits, '10.0' ) );
  }

  /**
   * Test with request token with units
   */
  public function testUnitRequest() {
    $this->beforeExecute();

    // Get a env secret
    $envSecret = env('SECRET_TOKEN_KNISH');
    if (!$envSecret) {
      throw new \Exception('env.SECRET_TOKEN_KNISH is not set.');
    }

    // Create a env stackable units token
    $client = $this->createToken( $this->serverTokenSlug, $this->tokenUnits, $envSecret );


    // Request token & shadow wallet claim iterations
    $sendingTokenUnitCount = 4;
    for( $i = 0; $i < 2; $i++) {

      // Get token units part for a transaction
      $tokenUnits = array_slice( $this->tokenUnits, $i * $sendingTokenUnitCount, $sendingTokenUnitCount );

      // Sending token unit IDs
      $sendingTokenUnitIds = $this->getTokenUnitIds( $tokenUnits );

      // Request tokens
      $client = $this->requestToken( $client, $sendingTokenUnitIds, $this->getBatchId( $i + 1 ) );

      // Claim created shadow wallet
      $this->claimShadowWallet( $client, $this->serverTokenSlug );
    }

  }

  /**
   * @param string $secret
   *
   * @throws \ReflectionException
   */
  private function testUnitsErrorTransaction( string $secret ) {

    $client = $this->client( $secret );
    $toSecret = Crypto::generateSecret();


    // From & to wallets
    $fromWallet = $client->queryBalance( $this->tokenSlug )
      ->payload();
    $toWallet = ClientWallet::create( $toSecret, $this->tokenSlug );


    // --- 1
    $response = $this->rawTokenTransfer( $client, $fromWallet, $toWallet,
      1, [ ['undefined_unit_id','undefined_unit_name'] ], []
    );
    $this->assertEquals( $response->status(), 'rejected' );
    print_r($response->reason() . "\r\n");

    // --- 2
    $response = $this->rawTokenTransfer( $client, $fromWallet, $toWallet,
      1, [ 0 ], []
    );
    $this->assertEquals( $response->status(), 'rejected' );
    print_r($response->reason() . "\r\n");

    // --- 3
    $response = $this->rawTokenTransfer( $client, $fromWallet, $toWallet,
      2, [ 0 ], [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ]
    );
    $this->assertEquals( $response->status(), 'rejected' );
    print_r($response->reason() . "\r\n");

    // --- 3
    $response = $this->rawTokenTransfer( $client, $fromWallet, $toWallet,
      1, [ 0 ], [ 1, 2, 3, 4, ['undefined_unit_id','undefined_unit_name'], 6, 7, 8, 9, 10 ]
    );
    $this->assertEquals( $response->status(), 'rejected' );
    print_r($response->reason() . "\r\n");

  }

  /**
   * @param $client
   * @param ClientWallet $fromWallet
   * @param ClientWallet $toWallet
   * @param $amount
   * @param array $recipientTokenUnits
   * @param array $remainderTokenUnits
   *
   * @return mixed|\WishKnish\KnishIO\Client\Response\Response
   * @throws \Exception
   */
  private function rawTokenTransfer( $client, ClientWallet $fromWallet, ClientWallet $toWallet, $amount, array $recipientTokenUnits, array $remainderTokenUnits ) {

    // Convering token units indexes to the related rows
    $recipientTokenUnits = $this->convertToWalletUnits( $recipientTokenUnits );
    $remainderTokenUnits = $this->convertToWalletUnits( $remainderTokenUnits );

    // Set recipient token units
    $toWallet->tokenUnits = $recipientTokenUnits;

    // Remainder wallet
    $remainderWallet = ClientWallet::create( Crypto::generateSecret(), $this->tokenSlug, $toWallet->batchId );
    $remainderWallet->tokenUnits = $remainderTokenUnits;

    // Create a molecule with custom source wallet
    $molecule = $client->createMolecule( null, $fromWallet, $remainderWallet );

    // Create a query
    /** @var MutationTransferTokens $query */
    $query = $client->createMoleculeMutation( MutationTransferTokens::class, $molecule );

    // Init a molecule
    $query->fillMolecule( $toWallet, $amount );

    // Execute a query
    return $query->execute();
  }

  /**
   * @param array $data
   *
   * @return array
   */
  private function convertToWalletUnits( array $data ): array {
    foreach( $data as $key => $index) {
      if ( !is_array( $index ) ) {
        $data[ $key ] = array_get( $this->tokenUnits, $index, $index );
      }
    }
    return ClientWallet::getTokenUnits( $data );
  }

  /**
   * @param array $tokenUnits
   *
   * @return array
   */
  private function getTokenUnitIds( array $tokenUnits ): array {
    $tokenUnitIds = [];
    foreach( $tokenUnits as $tokenUnit ) {
      $tokenUnitIds[] = $tokenUnit[ 0 ];
    }
    return $tokenUnitIds;
  }

  /**
   * @param int $index
   *
   * @return string
   */
  private function getBatchId( int $index ) {
    return $this->batchPrefix . $index;
  }

  /**
   * @param KnishIOClient $client
   * @param array $tokenUnits
   * @param string $batchId
   *
   * @return mixed|KnishIOClient
   * @throws \ReflectionException
   */
  private function transfetToken( KnishIOClient $client, array $tokenUnitIds, string $batchId ) {

    // Data for recipient
    $toSecret = Crypto::generateSecret();
    $toBundle = Crypto::generateBundleHash( $toSecret );

    // Transferring
    $response = $client->transferToken( $toBundle, $this->tokenSlug, $tokenUnitIds, $batchId );
    $this->checkResponse($response);

    return $this->client( $toSecret );
  }

  /**
   * @param KnishIOClient $client
   * @param array $tokenUnitIds
   * @param string $batchId
   *
   * @return mixed|KnishIOClient
   * @throws \ReflectionException
   */
  private function requestToken( KnishIOClient $client, array $tokenUnitIds, string $batchId ) {

    // Data for recipient
    $toSecret = Crypto::generateSecret();
    $toBundle = Crypto::generateBundleHash( $toSecret );

    // Request tokens
    $response = $client->requestTokens( $this->serverTokenSlug, $tokenUnitIds, $toBundle, null, $batchId );

    return $this->client( $toSecret );
  }

  /**
   * @param KnishIOClient $client
   * @param string $tokenSlug
   *
   * @throws \Exception
   */
  private function claimShadowWallet( KnishIOClient $client, string $tokenSlug ) {

    // Get shadow wallets
    $shadowWallets = $client->queryShadowWallets( $tokenSlug );

    // Init recipient query
    foreach ( $shadowWallets as $shadowWallet ) {
      $response = $client->claimShadowWallet( $tokenSlug, $shadowWallet->batchId );
    }
  }

  /**
   * @return KnishIOClient
   * @throws \ReflectionException
   */
  private function createToken( string $tokenSlug, array $tokenUnits, string $secret = null ): KnishIOClient {

    // Initial code
    $this->beforeExecute ();

    $secret = $secret ?? Crypto::generateSecret();

    $client = $this->client( $secret );
    $response = $client->createUnitableToken( $tokenSlug, $tokenUnits, [
      'name'			=> $tokenSlug,
      'supply'		=> 'limited',
      'icon'			=> 'icon',
    ], $this->getBatchId( 0 ) );
    $this->checkResponse($response);

    return $client;
  }

}
