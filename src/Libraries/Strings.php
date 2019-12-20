<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Libraries;

use BI\BigInteger;

/**
 * Class Str
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Strings
{
	/**
	 * Chunks a string into array segments of equal size
	 *
	 * @param string $str
	 * @param integer $size
	 * @return array
	 */
	public static function chunkSubstr ( $str, $size )
	{
		$chunks = ( $size > 0 ) ? array_pad( [], ceil( mb_strlen( $str ) / $size ), 0 ) : [];
		$o = 0;

		foreach ( $chunks as $idx => $value ) {
			$chunks[ $idx ] = mb_substr( $str, $o, $size );
			$o += $size;
		}

		return $chunks;
	}

	/**
	 * Generates a cryptographically-secure pseudo-random string of the given length out of the given alphabet
	 *
	 * @param int $length
	 * @param string $alphabet
	 * @return string
	 */
	public static function randomString ( $length = 256, $alphabet = 'abcdef0123456789' )
	{
		$array = array_map( static function () use ( $length ) {
			return \random_int( 0, 255 );
		}, array_pad( [], $length, 0 ) );
		return implode( array_map( static function ( $int ) use ( $alphabet ) {
			return mb_chr( static::utf8CharCodeAt( $alphabet, $int % mb_strlen( $alphabet ) ) );
		}, $array ) );
	}

	/**
	 * Convert charset between bases and alphabets
	 *
	 * @param string $src
	 * @param integer $fromBase
	 * @param integer $toBase
	 * @param string|null $srcSymbolTable
	 * @param string|null $destSymbolTable
	 * @return string
	 */
	public static function charsetBaseConvert ( $src, $fromBase, $toBase, $srcSymbolTable = null, $destSymbolTable = null )
	{
		// The reasoning behind capital first is because it comes first in a ASCII/Unicode character map 96 symbols support up to base 96
		$baseSymbols = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz~`!@#$%^&*()-_=+[{]}\\|;:\'",<.>/?¿¡';

		// Default the symbol table to a nice default table that supports up to base 96
		$srcSymbolTable = $srcSymbolTable ?: $baseSymbols;

		// Default the desttable equal to the srctable if it isn't defined
		$destSymbolTable = $destSymbolTable ?: $srcSymbolTable;

		// Make sure we are not trying to convert out of the symbol table range
		if ( $fromBase > mb_strlen( $srcSymbolTable ) || $toBase > mb_strlen( $destSymbolTable ) ) {
			error_log( 'Can\'t convert ' . $src . ' to base ' . $toBase . ' greater than symbol table length. src-table: ' . mb_strlen( $srcSymbolTable ) . ' dest-table: ' . mb_strlen( $destSymbolTable ) );
			return false;
		}

		// First convert to base 10
		$value = new BigInteger( 0 );
		$bigIntegerZero = new BigInteger( 0 );
		$bigIntegerToBase = new BigInteger( $toBase );
		$bigIntegerFromBase = new BigInteger( $fromBase );
		$srcSymbolList = str_split( $srcSymbolTable );

		for ( $i = 0, $length = mb_strlen( $src ); $i < $length; $i++ ) {

			$value = $value->mul( $bigIntegerFromBase )
				->add( new BigInteger( array_search( $src[ $i ], $srcSymbolList, true ) ) );
		}

		if ( $value->cmp( $bigIntegerZero ) <= 0 ) {
			return 0;
		}

		// Then covert to any base
		$target = '';

		do {
			$idx = $value->mod( $bigIntegerToBase );
			$target = $destSymbolTable[ $idx->toString() ] . $target;
			$value = $value->div( $bigIntegerToBase );
		} while ( !$value->equals( $bigIntegerZero ) );

		return $target;
	}


	/**
	 * @return string
	 */
	public static function currentTimeMillis ()
	{
		return ( string ) round( array_sum( explode( ' ', microtime() ) ) * 1000 );
	}

	/**
	 * @param string $str
	 * @param int $index
	 * @return int
	 */
	private static function utf8CharCodeAt ( $str, $index )
	{
		$char = mb_substr( $str, $index, 1, 'UTF-8' );
		return mb_check_encoding( $char, 'UTF-8' ) ? hexdec( bin2hex( mb_convert_encoding( $char, 'UTF-16BE', 'UTF-8' ) ) ) : 0;
	}


	/**
	 * Compresses a given string for web sharing
	 *
	 * @param string $str
	 * @return string
	 */
	public static function hexToBase64 ( $str )
	{
		return \base64_encode( \hex2bin( $str ) );
	}

	/**
	 * Decompresses a compressed string
	 *
	 * @param string $str
	 * @return string
	 */
	public static function base64ToHex ( $str )
	{
		return \bin2hex( \base64_decode( $str ) );
	}

}
