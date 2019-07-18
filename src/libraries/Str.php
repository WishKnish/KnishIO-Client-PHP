<?php
namespace WishKnish\KnishIO\Client\libraries;

use BI\BigInteger;

/**
 * Class Str
 * @package WishKnish\KnishIO\Client\libraries
 */
class Str
{
    /**
     * @param string $str
     * @param integer $size
     * @return array
     */
    public static function chunkSubstr ( $str, $size )
    {
        $chunks = ( 0 < $size ) ? array_pad( [], ceil( mb_strlen( $str ) / $size ), 0 ) : [];
        $o = 0;

        foreach ( $chunks as $idx => $value ) {
            $chunks[$idx] = mb_substr( $str, $o, $size );
            $o += $size;
        }

        return $chunks;
    }

    /**
     * @param int $length
     * @param string $alphabet
     * @return string
     */
    public static function randomString ( $length = 256, $alphabet = 'abcdef0123456789' )
    {
        $array = array_map( static function () use ( $length ) { return random_int( 0, 255 );  }, array_pad( [], $length, 0 ) );
        return implode( array_map( static function ( $int ) use ( $alphabet ) { return mb_chr( static::utf8CharCodeAt( $alphabet, $int % mb_strlen( $alphabet ) ) );  }, $array ) );
    }

    /**
     * @param string $src
     * @param integer $from_base
     * @param integer $to_base
     * @param string|null $src_symbol_table
     * @param string|null $dest_symbol_table
     * @return string
     */
    public static function charsetBaseConvert ( $src, $from_base, $to_base, $src_symbol_table = null, $dest_symbol_table = null )
    {
        // The reasoning behind capital first is because it comes first in a ASCII/Unicode character map 96 symbols support up to base 96
        $base_symbols = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz~`!@#$%^&*()-_=+[{]}\\|;:\'",<.>/?¿¡';

        // Default the symbol table to a nice default table that supports up to base 96
        $src_symbol_table = $src_symbol_table ?: $base_symbols;

        // Default the desttable equal to the srctable if it isn't defined
        $dest_symbol_table = $dest_symbol_table ?: $src_symbol_table;

        // Make sure we are not trying to convert out of the symbol table range
        if ( $from_base > mb_strlen( $src_symbol_table ) || $to_base > mb_strlen( $dest_symbol_table ) ) {
            error_log( 'Can\'t convert ' . $src .  ' to base ' . $to_base . ' greater than symbol table length. src-table: ' . mb_strlen( $src_symbol_table ) . ' dest-table: ' . mb_strlen( $dest_symbol_table ) );
            return false;
        }

        // First convert to base 10
        $value = new BigInteger( 0 );
        $big_integer_zero = new BigInteger( 0 );
        $big_integer_to_base = new BigInteger( $to_base );
        $big_integer_from_base = new BigInteger( $from_base );
        $src_symbol_list = str_split( $src_symbol_table );

        for( $i = 0, $length = mb_strlen( $src ); $i < $length; $i ++ ) {

            $value = $value->mul( $big_integer_from_base )
                ->add( new BigInteger( array_search( $src[$i], $src_symbol_list, true ) ) );
        }

        if ( $value->cmp( $big_integer_zero ) <= 0 ) {
            return 0;
        }

        // Then covert to any base
        $target = '';

        do {
            $idx = $value->mod( $big_integer_to_base );
            $target = $dest_symbol_table[$idx->toString()] . $target;
            $value = $value->div( $big_integer_to_base );
        }
        while ( ! $value->equals( $big_integer_zero ) );

        return $target;
    }


    /**
     * @return string
     */
    public static function currentTimeMillis ()
    {
        return ( string ) round(array_sum( explode( ' ' , microtime() ) ) * 1000 );
    }

    /**
     * @param string $str
     * @param int $index
     * @return int
     */
    private static function utf8CharCodeAt ( $str, $index )
    {
        $char = mb_substr( $str, $index, 1, 'UTF-8' );
        return mb_check_encoding( $char, 'UTF-8' ) ? hexdec( bin2hex( mb_convert_encoding( $char, 'UTF-16BE', 'UTF-8' ) ) ) : 0 ;
    }
}
