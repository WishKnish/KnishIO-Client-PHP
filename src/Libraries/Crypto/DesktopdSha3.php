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

namespace WishKnish\KnishIO\Client\Libraries\Crypto;

/* -*- coding: utf-8; indent-tabs-mode: t; tab-width: 4 -*-
vim: ts=4 noet ai */

use Exception;

/**
 * Streamable SHA-3 for PHP 5.2+, with no lib/ext dependencies!
 *
 * Copyright © 2018  Desktopd Developers
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @license LGPL-3+
 * @file
 */

/**
 * SHA-3 (FIPS-202) for PHP strings (byte arrays) (PHP 5.2.1+)
 * PHP 7.0 computes SHA-3 about 4 times faster than PHP 5.2 - 5.6 (on x86_64)
 *
 * Based on the reference implementations, which are under CC-0
 * Reference: http://keccak.noekeon.org/
 *
 * This uses PHP's native byte strings. Supports 32-bit as well as 64-bit
 * systems. Also for LE vs. BE systems.
 */
class DesktopdSha3 {
  public const SHA3_224 = 1;
  public const SHA3_256 = 2;
  public const SHA3_384 = 3;
  public const SHA3_512 = 4;

  public const SHAKE128 = 5;
  public const SHAKE256 = 6;

  public static array $idxs = [ [ 0, 5, 10, 15, 20 ], [ 1, 6, 11, 16, 21 ], [ 2, 7, 12, 17, 22 ], [ 3, 8, 13, 18, 23 ], [ 4, 9, 14, 19, 24 ] ];

  /**
   * @param int|null $type
   *
   * @return DesktopdSha3
   * @throws Exception
   */
  public static function init ( int $type = null ): DesktopdSha3 {
    switch ( $type ) {
      case self::SHA3_224:
        return new self ( 1152, 448, 0x06, 28 );
      case self::SHA3_256:
        return new self ( 1088, 512, 0x06, 32 );
      case self::SHA3_384:
        return new self ( 832, 768, 0x06, 48 );
      case self::SHA3_512:
        return new self ( 576, 1024, 0x06, 64 );
      case self::SHAKE128:
        return new self ( 1344, 256, 0x1f );
      case self::SHAKE256:
        return new self ( 1088, 512, 0x1f );
    }

    throw new Exception ( 'Invalid operation type' );
  }

  /**
   * Feed input to SHA-3 "sponge"
   *
   * @param $data
   *
   * @return $this
   * @throws Exception
   */
  public function absorb ( $data ): DesktopdSha3 {
    if ( self::PHASE_INPUT !== $this->phase ) {
      throw new Exception ( 'No more input accepted' );
    }

    $rateInBytes = $this->rateInBytes;
    $this->inputBuffer .= $data;
    while ( strlen( $this->inputBuffer ) >= $rateInBytes ) {

      $input = substr( $this->inputBuffer, 0, $rateInBytes );
      $this->inputBuffer = substr( $this->inputBuffer, $rateInBytes );
      /*
      list ($input, $this->inputBuffer) = array(
        substr($this->inputBuffer, 0, $rateInBytes)
      , substr($this->inputBuffer, $rateInBytes));
      */

      $blockSize = $rateInBytes;
      for ( $i = 0; $i < $blockSize; $i++ ) {
        $this->state[ $i ] = $this->state[ $i ] ^ $input[ $i ];
      }

      $this->state = self::keccakF1600Permute( $this->state );
      $this->blockSize = 0;
    }

    return $this;
  }

  /**
   * Get hash output
   *
   * @param int|null $length
   *
   * @return string
   * @throws Exception
   */
  public function squeeze ( int $length = null ): string {
    $outputLength = $this->outputLength; // fixed length output
    if ( $length && 0 < $outputLength && $outputLength !== $length ) {
      throw new Exception ( 'Invalid length' );
    }

    if ( self::PHASE_INPUT === $this->phase ) {
      $this->finalizeInput();
    }

    if ( self::PHASE_OUTPUT !== $this->phase ) {
      throw new Exception ( 'No more output allowed' );
    }
    if ( 0 < $outputLength ) {
      $this->phase = self::PHASE_DONE;
      return $this->getOutputBytes( $outputLength );
    }

    $blockLength = $this->rateInBytes;
    [ $output, $this->outputBuffer ] = [ substr( $this->outputBuffer, 0, $length ), substr( $this->outputBuffer, $length ) ];
    $neededLength = $length - strlen( $output );
    $diff = $neededLength % $blockLength;
    if ( $diff ) {
      $readLength = ( ( $neededLength - $diff ) / $blockLength + 1 ) * $blockLength;
    }
    else {
      $readLength = $neededLength;
    }

    $read = $this->getOutputBytes( $readLength );
    $this->outputBuffer .= substr( $read, $neededLength );
    return $output . substr( $read, 0, $neededLength );
  }

  // internally used
  public const PHASE_INIT = 1;
  public const PHASE_INPUT = 2;
  public const PHASE_OUTPUT = 3;
  public const PHASE_DONE = 4;

  private int $phase;
  private string $state; // byte array (string)
  private int $rateInBytes; // positive integer
  private int $suffix; // 8-bit unsigned integer
  private string $inputBuffer = ''; // byte array (string): max length = rateInBytes
  private int $outputLength;
  private string $outputBuffer = '';

  /**
   * DesktopdSha3 constructor.
   *
   * @param int $rate
   * @param int $capacity
   * @param int $suffix
   * @param int $length
   */
  public function __construct ( int $rate, int $capacity, int $suffix, int $length = 0 ) {
    if ( 1600 !== ( $rate + $capacity ) ) {
      throw new Error ( 'Invalid parameters' );
    }
    if ( 0 !== ( $rate % 8 ) ) {
      throw new Error ( 'Invalid rate' );
    }

    $this->suffix = $suffix;
    $this->state = str_repeat( "\0", 200 );
    $this->blockSize = 0;

    $this->rateInBytes = $rate / 8;
    $this->outputLength = $length;
    $this->phase = self::PHASE_INPUT;
  }

  /**
   * @return void
   */
  protected function finalizeInput (): void {
    $this->phase = self::PHASE_OUTPUT;

    $input = $this->inputBuffer;
    $inputLength = strlen( $input );
    if ( 0 < $inputLength ) {
      $blockSize = $inputLength;
      for ( $i = 0; $i < $blockSize; $i++ ) {
        $this->state[ $i ] = $this->state[ $i ] ^ $input[ $i ];
      }

      $this->blockSize = $blockSize;
    }

    // Padding
    $rateInBytes = $this->rateInBytes;
    $this->state[ $this->blockSize ] = $this->state[ $this->blockSize ] ^ chr( $this->suffix );
    if ( ( $this->suffix & 0x80 ) !== 0 && $this->blockSize === ( $rateInBytes - 1 ) ) {
      $this->state = self::keccakF1600Permute( $this->state );
    }
    $this->state[ $rateInBytes - 1 ] = $this->state[ $rateInBytes - 1 ] ^ "\x80";
    $this->state = self::keccakF1600Permute( $this->state );
  }

  /**
   * @param int $outputLength
   *
   * @return string
   */
  protected function getOutputBytes ( int $outputLength ): string {
    // Squeeze
    $output = '';
    while ( 0 < $outputLength ) {
      $blockSize = min( $outputLength, $this->rateInBytes );
      $output .= substr( $this->state, 0, $blockSize );
      $outputLength -= $blockSize;
      if ( 0 < $outputLength ) {
        $this->state = self::keccakF1600Permute( $this->state );
      }
    }

    return $output;
  }

  /**
   * 1600-bit state version of Keccak's permutation
   *
   * @param string $state
   *
   * @return string
   */
  public static function keccakF1600Permute ( string $state ): string {
    // !!! --- Check function from the ext
    if ( function_exists( 'keccakF1600Permute' ) ) {
      return keccakF1600Permute( $state );
    }
    // !!! ---

    $lanes = str_split( $state, 8 );
    $R = 1;
    $values = "\1\2\4\10\20\40\100\200";

    // Init idxs
    /*
    $idxs = [];
    for ($x = 0; $x < 5; $x++) {
      for ($y = 0; $y < 5; $y++) {
        $idxs[$x][$y] = $x + 5 * $y; // x, y
      }
    }
    */

    $ts = [];
    for ( $t = 0; $t < 24; $t++ ) {
      $ts[] = ( ( $t + 1 ) * ( $t + 2 ) / 2 ) % 64;
    }

    for ( $round = 0; $round < 24; $round++ ) {
      // θ step
      $C = [];
      for ( $x = 0; $x < 5; $x++ ) {
        // (x, 0) (x, 1) (x, 2) (x, 3) (x, 4)
        $C[ $x ] = $lanes[ $x ] ^ $lanes[ $x + 5 ] ^ $lanes[ $x + 10 ] ^ $lanes[ $x + 15 ] ^ $lanes[ $x + 20 ];
      }

      for ( $x = 0; $x < 5; $x++ ) {
        //$D = $C[($x + 4) % 5] ^ self::rotL64 ($C[($x + 1) % 5], 1);
        $D = $C[ ( $x + 4 ) % 5 ] ^ self::rotL64One( $C[ ( $x + 1 ) % 5 ] );
        for ( $y = 0; $y < 5; $y++ ) {
          $idx = static::$idxs[ $x ][ $y ]; // x, y
          $lanes[ $idx ] ^= $D;
        }
      }
      unset ( $C, $D );

      // ρ and π steps
      $x = 1;
      $y = 0;
      $current = $lanes[ 1 ]; // x, y
      for ( $t = 0; $t < 24; $t++ ) {
        [ $x, $y ] = [ $y, ( 2 * $x + 3 * $y ) % 5 ];
        $idx = static::$idxs[ $x ][ $y ];
        [ $current, $lanes[ $idx ] ] = [ $lanes[ $idx ], self::rotL64( $current, $ts[ $t ] ) ];
      }
      unset ( $temp, $current );

      // χ step
      $temp = [];
      for ( $y = 0; $y < 5; $y++ ) {
        for ( $x = 0; $x < 5; $x++ ) {
          $temp[ $x ] = $lanes[ static::$idxs[ $x ][ $y ] ];
        }
        for ( $x = 0; $x < 5; $x++ ) {
          $lanes[ static::$idxs[ $x ][ $y ] ] = $temp[ $x ] ^ ( ( ~$temp[ ( $x + 1 ) % 5 ] ) & $temp[ ( $x + 2 ) % 5 ] );

        }
      }
      unset ( $temp );

      // ι step
      for ( $j = 0; $j < 7; $j++ ) {
        $R = ( ( $R << 1 ) ^ ( ( $R >> 7 ) * 0x71 ) ) & 0xff;
        if ( $R & 2 ) {
          $offset = ( 1 << $j ) - 1;
          $shift = $offset % 8;
          $octetShift = ( $offset - $shift ) / 8;
          $n = "\0\0\0\0\0\0\0\0";
          $n[ $octetShift ] = $values[ $shift ];

          $lanes[ 0 ] ^= $n;
          //^ self::rotL64 ("\1\0\0\0\0\0\0\0", (1 << $j) - 1);
        }
      }
    }

    return implode( $lanes );
  }

  /**
   * @param string $n
   * @param int $offset
   *
   * @return int
   */
  protected static function rotL64_64 ( string $n, int $offset ): int {
    return ( $n << $offset ) & ( $n >> ( 64 - $offset ) );
  }

  /**
   * 64-bit bitwise left rotation (Little endian)
   *
   * @param string $n
   * @param int $offset
   *
   * @return string
   */
  protected static function rotL64 ( string $n, int $offset ): string {

    //$n = (binary) $n;
    //$offset = ((int) $offset) % 64;
    //if (8 != strlen ($n)) throw new Exception ('Invalid number');
    //if ($offset < 0) throw new Exception ('Invalid offset');

    $shift = $offset % 8;
    $octetShift = ( $offset - $shift ) / 8;
    $n = substr( $n, -$octetShift ) . substr( $n, 0, -$octetShift );

    $overflow = 0x00;
    for ( $i = 0; $i < 8; $i++ ) {
      $a = ord( $n[ $i ] ) << $shift;
      $n[ $i ] = chr( 0xff & $a | $overflow );
      $overflow = $a >> 8;
    }
    $n[ 0 ] = chr( ord( $n[ 0 ] ) | $overflow );
    return $n;
  }

  /**
   * 64-bit bitwise left rotation (Little endian)
   *
   * @param string $n
   *
   * @return string
   */
  protected static function rotL64One ( string $n ): string {
    [ $n[ 0 ], $n[ 1 ], $n[ 2 ], $n[ 3 ], $n[ 4 ], $n[ 5 ], $n[ 6 ], $n[ 7 ] ] = [ chr( ( ( ord( $n[ 0 ] ) << 1 ) & 0xff ) ^ ( ord( $n[ 7 ] ) >> 7 ) ), chr( ( ( ord( $n[ 1 ] ) << 1 ) & 0xff ) ^ ( ord( $n[ 0 ] ) >> 7 ) ), chr( ( ( ord( $n[ 2 ] ) << 1 ) & 0xff ) ^ ( ord( $n[ 1 ] ) >> 7 ) ), chr( ( ( ord( $n[ 3 ] ) << 1 ) & 0xff ) ^ ( ord( $n[ 2 ] ) >> 7 ) ), chr( ( ( ord( $n[ 4 ] ) << 1 ) & 0xff ) ^ ( ord( $n[ 3 ] ) >> 7 ) ), chr( ( ( ord( $n[ 5 ] ) << 1 ) & 0xff ) ^ ( ord( $n[ 4 ] ) >> 7 ) ), chr( ( ( ord( $n[ 6 ] ) << 1 ) & 0xff ) ^ ( ord( $n[ 5 ] ) >> 7 ) ), chr( ( ( ord( $n[ 7 ] ) << 1 ) & 0xff ) ^ ( ord( $n[ 6 ] ) >> 7 ) ) ];
    return $n;
  }
}

