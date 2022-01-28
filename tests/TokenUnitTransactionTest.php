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
use RuntimeException;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Mutation\MutationTransferTokens;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Wallet as ClientWallet;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
use WishKnish\KnishIO\Tests\TokenServerTransactionTest;

/**
 * Class TokenUnitTransactionTest
 * @package WishKnish\KnishIO\Client\Tests
 */
class TokenUnitTransactionTest extends TestCase {
  private string $serverTokenSlug = 'UTENVSTACKUNIT';
  private string $tokenSlug = 'UTSTACKUNIT';
  private int $cascadeDeep = 4;

  private string $batchPrefix = 'batch_';

  private array $tokenUnits = [ [ 'unit_id_1', 'unit_name_1', 'unit_meta_1', ], [ 'unit_id_2', 'unit_name_2', 'unit_meta_2', ], [ 'unit_id_3', 'unit_name_3', 'unit_meta_3', ], [ 'unit_id_4', 'unit_name_4', 'unit_meta_4', ], [ 'unit_id_5', 'unit_name_5', 'unit_meta_5', ], [ 'unit_id_6', 'unit_name_6', 'unit_meta_6', ], [ 'unit_id_7', 'unit_name_7', 'unit_meta_7', ], [ 'unit_id_8', 'unit_name_8', 'unit_meta_8', ], [ 'unit_id_9', 'unit_name_9', 'unit_meta_9', ], [ 'unit_id_10', 'unit_name_10', 'unit_meta_10', ], [ 'unit_id_11', 'unit_name_11', 'unit_meta_11', ], ];

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
   * @throws GuzzleException
   * @throws Exception
   */
  public function testUnitTransaction (): void {
    $this->beforeExecute();

    $secret = Crypto::generateSecret();

    // Create a token
    $client = $this->createToken( $this->tokenSlug, $this->getBatchId( 0 ), $this->tokenUnits, $secret );

    // Previously test the transfer errors
    $this->testUnitsErrorTransaction( $secret );

    // Transferring through cascade
    for ( $i = 0; $i < $this->cascadeDeep; $i++ ) {

      // Create batchID
      $index = $i + 1;
      $batchId = $this->getBatchId( $index );

      // Get token units part for a transaction
      $tokenUnits = array_slice( $this->tokenUnits, ( $i + 1 ) * 2 );

      // Sending token unit IDs
      $sendingTokenUnitIds = $this->getTokenUnitIds( $tokenUnits );

      // Token transferring
      $client = $this->transferToken( $client, $batchId, $sendingTokenUnitIds );

      // Claim created shadow wallet
      $this->claimShadowWallet( $client, $this->tokenSlug );

      // Create a meta to custom batchID
      $client->createMeta( 'batch', $batchId, [ 'key_shared' => 'value_shared', "key_$index" => "value_$index", ] );

      // Burn tokens for the last transaction
      if ( $i === $this->cascadeDeep - 1 ) {
        for ( $j = 0; $j < 2; $j++ ) {
          $tokenUnits = array_slice( $this->tokenUnits, ( $i + 1 ) * 2 + $j, 1 );
          $response = $client->burnToken( $this->tokenSlug, 0, $this->getTokenUnitIds( $tokenUnits ) );
          $this->checkResponse( $response );
        }
      }
    }

    // Get metas for last batchID
    $response = $client->queryBalance( $this->tokenSlug );
    $this->assertEquals( array_get( $response->payload()->tokenUnits, '0.id' ), array_get( $this->tokenUnits, '10.0' ) );

    // Check wrong token units format

    $secret = Crypto::generateSecret();
    $tokenSlug = 'wrongToken';
    $batchId = $this->getBatchId( 0 );

    // --- Token create
    $tokenUnits = [ 'unit1', 'unit2', 'unit3' ];
    $response = $this->client( $secret )
      ->createToken( $tokenSlug, 0, [ 'name' => $tokenSlug, 'supply' => 'limited', 'icon' => 'icon', 'fungibility' => 'stackable', ], 'token_error', $tokenUnits );
    $this->assertEquals( $response->reason(), 'TokenResolver: Wrong token units format.' );

    // --- Token transfer
    // From & to wallets
    $fromWallet = $client->queryBalance( $this->tokenSlug )
      ->payload();
    $toWallet = ClientWallet::create( $secret, $this->tokenSlug );

    $query = $this->getRawTokenTransferQuery( $client, $fromWallet, $toWallet, 1, [ 10 ], [] );

    // Get molecule & override token unit value
    $molecule = $query->molecule();
    $molecule->atoms[ 0 ]->meta[ 0 ][ 'value' ] = '["unit_id_11"]';
    $molecule->sign();

    $response = $client->createMoleculeMutation( MutationProposeMolecule::class, $molecule )
      ->execute();
    $this->assertEquals( $response->reason(), 'AtomValueResolver: Wrong token units format.' );
  }

  /**
   * Test with request token with units
   * @throws Exception
   * @throws GuzzleException
   */
  public function testUnitRequest (): void {
    $this->beforeExecute();

    // Get a env secret
    $envSecret = env( 'SECRET_TOKEN_KNISH' );
    if ( !$envSecret ) {
      throw new RuntimeException( 'env.SECRET_TOKEN_KNISH is not set.' );
    }

    // Create a env stackable units token
    $client = $this->createToken( $this->serverTokenSlug, 'request_' . $this->getBatchId( 0 ), $this->tokenUnits, $envSecret );

    // Request token & shadow wallet claim iterations
    $sendingTokenUnitCount = 4;
    for ( $i = 0; $i < 2; $i++ ) {

      // Get token units part for a transaction
      $tokenUnits = array_slice( $this->tokenUnits, $i * $sendingTokenUnitCount, $sendingTokenUnitCount );

      // Sending token unit IDs
      $sendingTokenUnitIds = $this->getTokenUnitIds( $tokenUnits );

      // New batch ID
      $batchId = 'request_' . $this->getBatchId( $i + 1 );

      // Request tokens
      $client = $this->requestToken( $client, $sendingTokenUnitIds, $batchId );

      // Claim created shadow wallet
      $this->claimShadowWallet( $client, $this->serverTokenSlug );
    }

  }

  /**
   * @param string $secret
   *
   * @throws Exception
   * @throws GuzzleException
   */
  private function testUnitsErrorTransaction ( string $secret ): void {

    $client = $this->client( $secret );
    $toSecret = Crypto::generateSecret();

    // From & to wallets
    $fromWallet = $client->queryBalance( $this->tokenSlug )
      ->payload();
    $toWallet = ClientWallet::create( $toSecret, $this->tokenSlug );

    // --- 1
    $response = $this->rawTokenTransfer( $client, $fromWallet, $toWallet, 1, [ [ 'undefined_unit_id', 'undefined_unit_name' ] ], [] );
    $this->assertEquals( $response->status(), 'rejected' );
    print_r( $response->reason() . "\r\n" );

    // --- 2
    $response = $this->rawTokenTransfer( $client, $fromWallet, $toWallet, 1, [ 0 ], [] );
    $this->assertEquals( $response->status(), 'rejected' );
    print_r( $response->reason() . "\r\n" );

    // --- 3
    $response = $this->rawTokenTransfer( $client, $fromWallet, $toWallet, 2, [ 0 ], [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ] );
    $this->assertEquals( $response->status(), 'rejected' );
    print_r( $response->reason() . "\r\n" );

    // --- 3
    $response = $this->rawTokenTransfer( $client, $fromWallet, $toWallet, 1, [ 0 ], [ 1, 2, 3, 4, [ 'undefined_unit_id', 'undefined_unit_name' ], 6, 7, 8, 9, 10 ] );
    $this->assertEquals( $response->status(), 'rejected' );
    print_r( $response->reason() . "\r\n" );

  }

  /**
   * @param $client
   * @param ClientWallet $fromWallet
   * @param ClientWallet $toWallet
   * @param $amount
   * @param array $recipientTokenUnits
   * @param array $remainderTokenUnits
   *
   * @return Response
   * @throws Exception|GuzzleException
   */
  private function rawTokenTransfer ( $client, ClientWallet $fromWallet, ClientWallet $toWallet, $amount, array $recipientTokenUnits, array $remainderTokenUnits ): Response {
    return $this->getRawTokenTransferQuery( $client, $fromWallet, $toWallet, $amount, $recipientTokenUnits, $remainderTokenUnits )
      ->execute();
  }

  /**
   * @param $client
   * @param ClientWallet $fromWallet
   * @param ClientWallet $toWallet
   * @param $amount
   * @param array $recipientTokenUnits
   * @param array $remainderTokenUnits
   *
   * @return MutationTransferTokens
   * @throws Exception
   */
  private function getRawTokenTransferQuery ( $client, ClientWallet $fromWallet, ClientWallet $toWallet, $amount, array $recipientTokenUnits, array $remainderTokenUnits ): MutationTransferTokens {

    // Converting token units indexes to the related rows
    $recipientTokenUnits = $this->convertToWalletUnits( $recipientTokenUnits );
    $remainderTokenUnits = $this->convertToWalletUnits( $remainderTokenUnits );

    // Set recipient token units
    $toWallet->tokenUnits = $recipientTokenUnits;
    $toWallet->batchId = Crypto::generateBatchId();

    // Remainder wallet
    $remainderWallet = ClientWallet::create( Crypto::generateSecret(), $this->tokenSlug, $toWallet->batchId );
    $remainderWallet->tokenUnits = $remainderTokenUnits;
    $remainderWallet->batchId = $fromWallet->batchId;

    // Create a molecule with custom source wallet
    $molecule = $client->createMolecule( null, $fromWallet, $remainderWallet );

    // Create a query
    /** @var MutationTransferTokens $query */
    $query = $client->createMoleculeMutation( MutationTransferTokens::class, $molecule );

    // Init a molecule
    $query->fillMolecule( $toWallet, $amount );

    return $query;
  }

  /**
   * @param array $data
   *
   * @return array
   */
  private function convertToWalletUnits ( array $data ): array {
    foreach ( $data as $key => $index ) {
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
  private function getTokenUnitIds ( array $tokenUnits ): array {
    $tokenUnitIds = [];
    foreach ( $tokenUnits as $tokenUnit ) {
      $tokenUnitIds[] = $tokenUnit[ 0 ];
    }
    return $tokenUnitIds;
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
   * @param KnishIOClient $client
   * @param string $batchId
   * @param array $tokenUnitIds
   *
   * @return KnishIOClient
   * @throws GuzzleException
   * @throws Exception
   */
  private function transferToken ( KnishIOClient $client, string $batchId, array $tokenUnitIds ): KnishIOClient {

    // Data for recipient
    $toSecret = Crypto::generateSecret();
    $toBundle = Crypto::generateBundleHash( $toSecret );

    // Transferring
    $response = $client->transferToken( $toBundle, $this->tokenSlug, 0, $batchId, $tokenUnitIds );
    $this->checkResponse( $response );

    return $this->client( $toSecret );
  }

  /**
   * @param KnishIOClient $client
   * @param array $tokenUnitIds
   * @param string $batchId
   *
   * @return KnishIOClient
   * @throws GuzzleException
   * @throws Exception
   */
  private function requestToken ( KnishIOClient $client, array $tokenUnitIds, string $batchId ): KnishIOClient {

    // Data for recipient
    $toSecret = Crypto::generateSecret();
    $toBundle = Crypto::generateBundleHash( $toSecret );

    // Request tokens
    $response = $client->requestTokens( $this->serverTokenSlug, 0, $toBundle, [], $batchId, $tokenUnitIds );
    if ( !$response->success() ) {
      dd( $response->response() );
    }

    return $this->client( $toSecret );
  }

  /**
   * @param KnishIOClient $client
   * @param string $tokenSlug
   *
   * @throws Exception
   * @throws GuzzleException
   */
  private function claimShadowWallet ( KnishIOClient $client, string $tokenSlug ): void {

    // Get shadow wallets
    foreach ( $client->queryShadowWallets( $tokenSlug ) as $shadowWallet ) {

      // Init recipient query
      $client->claimShadowWallet( $tokenSlug, $shadowWallet->batchId );
    }
  }

  /**
   * @param string $tokenSlug
   * @param string $batchId
   * @param array $tokenUnits
   * @param string|null $secret
   *
   * @return KnishIOClient
   * @throws GuzzleException
   * @throws Exception
   */
  private function createToken ( string $tokenSlug, string $batchId, array $tokenUnits, string $secret = null ): KnishIOClient {

    $secret = $secret ?? Crypto::generateSecret();

    $client = $this->client( $secret );
    $response = $client->createToken( $tokenSlug, 0, [ 'name' => $tokenSlug, 'supply' => 'limited', 'icon' => 'icon', 'fungibility' => 'stackable', ], $batchId, $tokenUnits );
    $this->checkResponse( $response );

    return $client;
  }

}
