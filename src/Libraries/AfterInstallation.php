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

namespace WishKnish\KnishIO\Client\Libraries;

/**
 * Class AfterInstallation
 * @package WishKnish\KnishIO\Client\Libraries
 */
class AfterInstallation {
  /**
   * Fixes unnecessary output in desktopd/php-sha3-streamable and PHP 8.2+ deprecation warnings
   */
  public static function sha3Fix (): void {
    // Fix Windows-specific output issues
    if ( PHP_OS_FAMILY === 'Windows' ) {

      $files = [
        implode( DIRECTORY_SEPARATOR, [
          __DIR__,
          '..',
          '..',
          'vendor',
          'desktopd',
          'php-sha3-streamable',
          'src',
          'SHA3.php'
        ] ),
        implode( DIRECTORY_SEPARATOR, [
          __DIR__,
          '..',
          '..',
          '..',
          '..',
          'desktopd',
          'php-sha3-streamable',
          'src',
          'SHA3.php'
        ] ),
      ];

      foreach ( $files as $file ) {

        if ( is_file( $file ) && is_writable( $file ) ) {
          $file = fopen( $file, 'wb' );

          if ( $file ) {
            fclose( $file );
          }
        }
      }
    }

    // Fix PHP 8.2+ deprecation warnings for dynamic properties
    self::fixDynamicPropertyWarnings();
  }

  /**
   * Fixes PHP 8.2+ deprecation warnings for dynamic properties in Sponge class
   */
  private static function fixDynamicPropertyWarnings(): void {
    $spongePaths = [
      implode( DIRECTORY_SEPARATOR, [
        __DIR__,
        '..',
        '..',
        'vendor',
        'desktopd',
        'php-sha3-streamable',
        'namespaced',
        'desktopd',
        'SHA3',
        'Sponge.php'
      ] ),
      implode( DIRECTORY_SEPARATOR, [
        __DIR__,
        '..',
        '..',
        '..',
        '..',
        'desktopd',
        'php-sha3-streamable',
        'namespaced',
        'desktopd',
        'SHA3',
        'Sponge.php'
      ] ),
    ];

    foreach ( $spongePaths as $spongeFile ) {
      if ( is_file( $spongeFile ) && is_readable( $spongeFile ) && is_writable( $spongeFile ) ) {
        $content = file_get_contents( $spongeFile );

        // Check if already patched
        if ( strpos( $content, 'private $blockSize' ) !== false ) {
          continue; // Already fixed
        }

        // Add the missing blockSize property declaration
        $search = 'private $native64bit = false;';
        $replace = "private \$native64bit = false;\n\tprivate \$blockSize = 0; // Added by AfterInstallation::sha3Fix() for PHP 8.2+ compatibility";

        $patched = str_replace( $search, $replace, $content );

        if ( $patched !== $content ) {
          file_put_contents( $spongeFile, $patched );
          echo "✅ Patched Sponge.php for PHP 8.2+ compatibility at: {$spongeFile}\n";
        }
      }
    }
  }
}
