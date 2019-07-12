<?php
namespace WishKnish\KnishIO\Client\libraries;

/**
 * Class AfterInstallation
 * @package WishKnish\KnishIO\Client\libraries
 */
class AfterInstallation
{
    /**
     * Fixes unnecessary output in desktopd/php-sha3-streamable
     */
    public static function sha3Fix ()
    {
        if ( 0 === stripos( PHP_OS, 'win' ) ) {

            $files = [
                implode(DIRECTORY_SEPARATOR, [
                    __DIR__,
                    '..',
                    '..',
                    'vendor',
                    'desktopd',
                    'php-sha3-streamable',
                    'src',
                    'SHA3.php'
                ]),
                implode(DIRECTORY_SEPARATOR, [
                    __DIR__,
                    '..',
                    '..',
                    '..',
                    '..',
                    'desktopd',
                    'php-sha3-streamable',
                    'src',
                    'SHA3.php'
                ]),
            ];

            foreach ( $files as $file ) {

                if ( is_file( $file ) ) {
                    $file = fopen( $file, 'wb' );

                    if ( $file ) {
                        fclose( $file );
                    }
                }
            }
        }
    }
}