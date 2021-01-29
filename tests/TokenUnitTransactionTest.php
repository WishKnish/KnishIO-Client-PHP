<?php

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Tests\TestCase;
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
  private $tokenSlug = 'UTSTACKUNIT';
  private $transactionAmount = 2;
  private $cascadeDeep = 5;
  private $batchPrefix = 'batch_';
  private $units = [
    [ 'unit_id_1', 'unit_name_1', 'unit_meta_1', ],
    [ 'unit_id_2', 'unit_name_2', 'unit_meta_2', ],
    [ 'unit_id_3', 'unit_name_3', 'unit_meta_3', ],
    [ 'unit_id_4', 'unit_name_4', 'unit_meta_4', ],
    [ 'unit_id_5', 'unit_name_5', 'unit_meta_5', ],
    [ 'unit_id_6', 'unit_name_6', 'unit_meta_6', ],
    [ 'unit_id_7', 'unit_name_7', 'unit_meta_7', ],
    [ 'unit_id_8', 'unit_name_8', 'unit_meta_8', ],
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
   * @throws ReflectionException
   */
  public function testCascadeBatch() {

    // Create a token
    $client = $this->createToken();
    $transactionAmount = $this->transactionAmount;

    // Transferring through cascade
    for ( $i = 0; $i < $this->cascadeDeep; $i++ ) {

      // Create batchID
      $index = $i + 1;
      $batchId = $this->getBatchId( $index );

      // Token transferring
      $client = $this->transfetToken( $client, $transactionAmount, $batchId );

      // Claim created shadow wallet
      $this->claimShadowWallet( $client );

      // Create a meta to custom batchID
      $client->createMeta( 'batch', $batchId, [
        'key_shared' => 'value_shared',
        "key_$index" => "value_$index",
      ] );

      // Change transaction amount for each step
      $transactionAmount -= 10;

      // Burn tokens for the last transaction
      if ( $i === $this->cascadeDeep - 1 ) {
        $client->burnToken( $this->tokenSlug,5, $this->getBatchId( $index + 1) );
        $client->burnToken( $this->tokenSlug,5, $this->getBatchId( $index + 2) );
      }
    }


    // Get metas for last batchID
    $response = (new QueryBatch( $client->client() ))->execute([
      'batchId' => $batchId,
    ]);
    dd( $response->data() );
  }


  /**
   * @throws ReflectionException
   */
  public function testUnitToken() {

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
   * @param array $units
   * @param string $batchId
   *
   * @return mixed|KnishIOClient
   * @throws \ReflectionException
   */
  private function transfetToken( KnishIOClient $client, array $units, string $batchId ) {

    // Initial code
    $this->beforeExecute ();

    // Data for recipient
    $toSecret = Crypto::generateSecret();
    $toBundle = Crypto::generateBundleHash( $toSecret );

    // Transferring
    $response = $client->transferToken($toBundle, $this->tokenSlug, count( $units ), $batchId);
    $this->checkResponse($response);

    return $this->client( $toSecret );
  }

  /**
   * @param KnishIOClient $client
   *
   * @throws \Exception
   */
  private function claimShadowWallet( KnishIOClient $client ) {

    // Get shadow wallets
    $shadowWallets = $client->queryShadowWallets( $this->tokenSlug );

    // Init recipient query
    foreach ( $shadowWallets as $shadowWallet ) {
      $response = $client->claimShadowWallet( $this->tokenSlug, $shadowWallet->batchId );
    }
  }

  /**
   * @return KnishIOClient
   * @throws \ReflectionException
   */
  private function createToken(): KnishIOClient {

    // Initial code
    $this->beforeExecute ();

    $client = $this->client(Crypto::generateSecret());
    $response = $client->createUnitableToken( $this->tokenSlug, $this->units, [
      'name'			=> $this->tokenSlug,
      'supply'		=> 'limited',
      'icon'			=> 'icon',
    ], $this->getBatchId( 0 ) );
    $this->checkResponse($response);

    return $client;
  }

}
