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

use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Tests\TestCase;
use WishKnish\KnishIO\Client\Wallet as ClientWallet;
use WishKnish\KnishIO\Client\Query\QueryBatch;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
use WishKnish\KnishIO\Client\Query\QueryWalletList;

/**
 * Class QueryCascadeMetaTest
 */
class QueryCascadeBatchTest extends TestCase
{
  private $tokenSlug = 'UTSTACKABLE';
  private $fullAmount = 1000;
  private $transactionAmount = 100;
  private $cascadeDeep = 5;
  private $batchPrefix = 'batch_';


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
   * @throws ReflectionException
   */
  private function transfetToken( $client, $transactionAmount, $batchId ) {

    // Initial code
    $this->beforeExecute ();

    // Data for recipient
    $toSecret = Crypto::generateSecret();
    $toBundle = Crypto::generateBundleHash( $toSecret );

    // Transferring
    $response = $client->transferToken($toBundle, $this->tokenSlug, $transactionAmount, $batchId);
    $this->checkResponse($response);

    return $this->client( $toSecret );
  }


  /**
   * @throws Exception
   */
  private function claimShadowWallet( $client ) {

    // Get shadow wallets
    $shadowWallets = $client->queryShadowWallets( $this->tokenSlug );

    // Init recipient query
    foreach ( $shadowWallets as $shadowWallet ) {
      $response = $client->claimShadowWallet( $this->tokenSlug, $shadowWallet->batchId );
    }
  }


  /**
   * @throws ReflectionException
   */
  private function createToken() {

    // Initial code
    $this->beforeExecute ();

    $client = $this->client(Crypto::generateSecret());
    $response = $client->createToken($this->tokenSlug, $this->fullAmount, [
      'name'			=> $this->tokenSlug,
      'fungibility'	=> 'stackable',
      'splittable'	=> 1,
      'supply'		=> 'limited',
      'decimals'		=> 0,
      'icon'			=> 'icon',
    ], $this->getBatchId( 0 ) );
    $this->checkResponse($response);

    return $client;
  }

}
