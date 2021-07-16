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
use ReflectionException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Query\QueryBatch;
use WishKnish\KnishIO\Tests\TokenServerTransactionTest;

/**
 * Class QueryCascadeMetaTest
 */
class QueryCascadeBatchTest extends TestCase {
  private string $tokenSlug = 'UTSTACKABLE';
  private int $fullAmount = 1000;
  private int $transactionAmount = 100;
  private int $cascadeDeep = 5;
  private string $batchPrefix = 'batch_';

  /**
   * Clear data test
   *
   * @throws ReflectionException
   * @throws Exception
   */
  public function testClearAll (): void {

    // Initial code
    $this->beforeExecute();

    // Call server cleanup
    $this->callServerCleanup( TokenServerTransactionTest::class );

    // Default assertion
    $this->assertEquals( true, true );
  }

  /**
   * @throws ReflectionException|GuzzleException
   * @throws Exception
   */
  public function testCascadeBatch (): void {

    // Create a token
    $client = $this->createToken();
    $transactionAmount = $this->transactionAmount;

    // Transferring through cascade
    for ( $i = 0; $i < $this->cascadeDeep; $i++ ) {

      // Create batchID
      $index = $i + 1;
      $batchId = $this->getBatchId( $index );

      // Token transferring
      $client = $this->transferToken( $client, $transactionAmount, $batchId );

      // Claim created shadow wallet
      $this->claimShadowWallet( $client );

      // Create a meta to custom batchID
      $client->createMeta( 'batch', $batchId, [ 'key_shared' => 'value_shared', "key_$index" => "value_$index", ] );

      // Change transaction amount for each step
      $transactionAmount -= 10;

      // Burn tokens for the last transaction
      if ( $i === $this->cascadeDeep - 1 ) {
        $client->burnToken( $this->tokenSlug, 5 );
        $client->burnToken( $this->tokenSlug, 5 );
      }
    }

    // Get metas for last batchID
    $response = ( new QueryBatch( $client->client() ) )->execute( [ 'batchId' => $batchId, ] );
    dd( $response->data() );
  }

  public function testUnitToken () {

  }

  /**
   * @param int $index
   *
   * @return string
   */
  private function getBatchId ( int $index ): string {
    return $this->batchPrefix . $index;
  }

  /**
   * @throws Exception
   * @throws GuzzleException
   */
  private function transferToken ( $client, $transactionAmount, $batchId ) {

    // Initial code
    $this->beforeExecute();

    // Data for recipient
    $toSecret = Crypto::generateSecret();
    $toBundle = Crypto::generateBundleHash( $toSecret );

    // Transferring
    $response = $client->transferToken( $toBundle, $this->tokenSlug, $transactionAmount, $batchId );
    $this->checkResponse( $response );

    return $this->client( $toSecret );
  }

  /**
   * @throws Exception
   */
  private function claimShadowWallet ( $client ): void {

    // Get shadow wallets
    $shadowWallets = $client->queryShadowWallets( $this->tokenSlug );

    // Init recipient query
    foreach ( $shadowWallets as $shadowWallet ) {
      $response = $client->claimShadowWallet( $this->tokenSlug, $shadowWallet->batchId );
    }
  }

  /**
   * @throws ReflectionException|GuzzleException
   * @throws Exception
   */
  private function createToken () {

    // Initial code
    $this->beforeExecute();

    $client = $this->client( Crypto::generateSecret() );
    $response = $client->createToken( $this->tokenSlug, $this->fullAmount, [ 'name' => $this->tokenSlug, 'fungibility' => 'stackable', 'splittable' => 1, 'supply' => 'limited', 'decimals' => 0, 'icon' => 'icon', ], $this->getBatchId( 0 ) );
    $this->checkResponse( $response );

    return $client;
  }

}
