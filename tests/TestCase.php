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
use PHPUnit\TextUI\Command;

/**
 * Class TestCase
 * @package WishKnish\KnishIO\Client\Tests
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase {



  /**
   * Before execute
   *
   * @throws Exception
   */
  protected function beforeExecute (): void {
  }


  /**
   * Output
   *
   * @param array|string $info
   */
  protected function output ( array|string $info ): void {
    if ( is_array( $info ) ) {
      $info = implode( "\r\n", $info );
    }
    echo $info . "\r\n\r\n";
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
      $command->run( [ 'phpunit', '--configuration', __DIR__ . '/../' . 'phpunit.xml', '--filter', '/(::' . $test . ')( .*)?$/', $class, $server_test_filepath, '--teamcity', ], false );
    }
    $this->assertEquals( true, true );
  }

  /**
   */
  protected function callServerCleanup ( $class ): void {
    $this->callThirdPartyTest( $class, 'testClearAll', getenv( 'SERVER_TEST_PATH' ) );
  }

}
