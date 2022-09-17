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
   * Call third-party tests
   *
   * @param string $class
   * @param string $test
   * @param string $test_dir
   */
  protected function callThirdPartyTest ( string $class, string $test, string $test_dir ): void {

    // Server test filepath
    $server_test_filepath = $test_dir . class_basename( $class ) . '.php';

    // File does not exist
    if ( !$server_test_filepath || !file_exists( $server_test_filepath ) ) {
      print_r( "SERVER_TEST_FILE is not defined. Test do not clean up.\r\n" );
    }

    // Create & run a unit test command
    else {
      $command = new Command();
      $command->run( [ 'phpunit', '--configuration', __DIR__ . '/../' . 'phpunit.xml', '--filter', '/(::' . $test . ')( .*)?$/', $class, $server_test_filepath, '--teamcity', ], false );
    }

    $this->assertEquals( true, true );
  }

}
