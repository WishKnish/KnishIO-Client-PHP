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

// Supporting variety versions of PHPUnit
use Dotenv\Dotenv;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\TextUI\Command;
use PHPUnit_Framework_TestCase;
use ReflectionException;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;
use function default_if_null;
use function json_encode;

if ( !class_exists( '\PHPUnit_Framework_TestCase' ) ) {
  abstract class TestCaseBase extends \PHPUnit\Framework\TestCase { }
}
else {
  abstract class TestCaseBase extends PHPUnit_Framework_TestCase { }
}

/**
 * Class TestCase
 * @package WishKnish\KnishIO\Client\Tests
 */
abstract class TestCase extends TestCaseBase {

  protected $client;
  protected $dotenv;

  protected string $cell_slug = 'unit_test';
  protected ?string $graphql_url = null;

  // Array [secret1 => KnishIOClient object1, secret2 => KnishIOClient object2, ..]
  protected array $clients = [];

  /**
   * Data filepath
   *
   * @return string
   */
  protected function dataFilepath (): string {
    return __DIR__ . '/' . substr( strrchr( static::class, "\\" ), 1 ) . '.data';
  }

  /**
   * Save data
   *
   * @param array $data
   * @param null $filepath
   */
  protected function saveData ( array $data, $filepath = null ): void {
    $filepath = default_if_null( $filepath, $this->dataFilepath() );
    file_put_contents( $filepath, json_encode( $data ) );
  }

  /**
   * @return mixed
   */
  protected function getData ( $filepath = null ) {
    $filepath = default_if_null( $filepath, $this->dataFilepath() );
    return json_decode( file_get_contents( $filepath ), true );
  }

  /**
   * @param string|null $filepath
   */
  protected function clearData ( string $filepath = null ): void {
    $filepath = default_if_null( $filepath, $this->dataFilepath() );
    if ( file_exists( $filepath ) ) {
      unlink( $filepath );
    }
  }

  /**
   * Before execute
   *
   * @throws Exception
   */
  protected function beforeExecute () {

    // Load env
    $env_path = __DIR__ . '/../';
    $env_file = implode( '.', array_filter( [ '.env', getenv( 'APP_ENV' ) ] ) );
    if ( is_dir( $env_path ) ) {

      // Switch between dotenv versions
      if ( method_exists( '\Dotenv\Dotenv', 'createImmutable' ) ) {
        $this->dotenv = Dotenv::createImmutable( $env_path, $env_file );
      }
      else {
        $this->dotenv = Dotenv::create( $env_path, $env_file );
      }

      $this->dotenv->load();
    }

    // Get an app url
    $app_url = getenv( 'APP_URL' );

    // Check app url
    if ( !$app_url ) {
      throw new Exception( 'APP_URL is empty.' );
    }

    // GraphQL url
    if ( $this->graphql_url === null ) {
      $this->graphql_url = $app_url . 'graphql';
    }

    // Client initialization
    $this->output( [ 'Query URL: ' . $this->graphql_url ] );
  }

  /**
   * Get a client
   *
   * @param string $secret
   * @param null $cell_slug
   *
   * @return mixed
   * @throws GuzzleException
   */
  public function client ( string $secret, $cell_slug = null ) {

    $cell_slug = $cell_slug ?: $this->cell_slug;

    // Create new client
    if ( !array_has( $this->clients, $secret ) ) {

      // Create a client
      $this->clients[ $secret ] = new KnishIOClient( $this->graphql_url );

      // Auth the client
      // $response = $this->clients[ $secret ]->requestAuthToken( $secret, $cell_slug );
      // $this->checkResponse( $response );
      $authToken = $this->clients[ $secret ]->authorize( $secret, $cell_slug );
    }

    // Return the client by secret
    return $this->clients[ $secret ];
  }

  /**
   * @param $secret
   * @param $molecule
   *
   * @return Response
   * @throws GuzzleException
   */
  protected function executeMolecule ( $secret, $molecule ): Response {

    // Execute query & check response
    /**
     * @var MutationProposeMolecule $mutation
     */
    $mutation = $this->client( $secret )
        ->createMoleculeMutation( MutationProposeMolecule::class, $molecule );
    $response = $mutation
        ->execute();

    // Check the response
    $this->checkResponse( $response );

    return $response;
  }

  /**
   * @param Response $response
   */
  protected function checkResponse ( Response $response ): void {

    // Check molecule response
    if ( $response instanceof ResponseMolecule ) {
      if ( !$response->success() ) {
        $this->debug( $response, true );
      }
      $this->assertEquals( $response->status(), 'accepted' );
    }

    // Default response
    else if ( !$response->data() ) {
      $this->debug( $response, true );
    }
  }

  /**
   * @param Response $response
   * @param bool $final
   */
  protected function debug ( Response $response, bool $final = false ): void {

    // Debug output
    $output = [ 'query' => get_class( $response->query() ), 'url' => $response->query()
        ->uri(), ];

    // Reason data on the top of the output
    if ( array_has( $response->data(), 'reason' ) ) {
      $output[ 'reason' ] = array_get( $response->data(), 'reason' );
      $output[ 'payload' ] = array_get( $response->data(), 'payload' );
    }

    // Other debug info
    $output = array_merge( $output, [ 'variables' => $response->query()
        ->variables(), 'response' => $response->response(), ] );

    print_r( $output );
    if ( $final ) {
      die ();
    }
  }

  /**
   * Output
   *
   * @param array $info
   */
  protected function output ( array $info ): void {
    echo implode( "\r\n", $info ) . "\r\n\r\n";
  }

  /**
   * Clear data test
   *
   */
  protected function callThirdPartyTest ( $class, $test, $test_dir ): void {

    // PHP version
    $this->output( [ 'PHP Version: ' . PHP_VERSION ] );

    // PHP version comparing
    if ( version_compare( PHP_VERSION, '7.0.0' ) <= 0 ) {
      $this->output( [ "PHP version is less than 7.0.0. Skip '$test' test.", "  -- DB must be cleaned manually", "  -- OR should call $class::$test server unit test instead.", ] );
      return;
    }

    // Server test filepath
    $server_test_filepath = $test_dir . class_basename( $class ) . '.php';

    // File does not exist
    if ( !$server_test_filepath || !file_exists( $server_test_filepath ) ) {
      print_r( "SERVER_TEST_FILE is not defined. Test do not clean up.\r\n" );
    }
    else {

      // Create & run a unit test command
      $command = new Command();
      $response = $command->run( [ 'phpunit', '--configuration', __DIR__ . '/../' . 'phpunit.xml', '--filter', '/(::' . $test . ')( .*)?$/', $class, $server_test_filepath, '--teamcity', ], false );
    }
    $this->assertEquals( true, true );
  }

  /**
   */
  protected function callServerCleanup ( $class ): void {
    $this->callThirdPartyTest( $class, 'testClearAll', getenv( 'SERVER_TEST_PATH' ) );
  }

}
