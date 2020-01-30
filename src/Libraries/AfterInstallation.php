<?php

namespace WishKnish\KnishIO\Client\Libraries;

/**
 * Class AfterInstallation
 * @package WishKnish\KnishIO\Client\Libraries
 */
class AfterInstallation
{
	/**
	 * Fixes unnecessary output in desktopd/php-sha3-streamable
	 */
	public static function sha3Fix ()
	{
		if ( stripos( PHP_OS, 'win' ) === 0 ) {

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
	}
}
