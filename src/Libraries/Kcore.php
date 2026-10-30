<?php

namespace WishKnish\KnishIO\Client\Libraries;

use FFI;
use InvalidArgumentException;
use Throwable;
use WishKnish\KnishIO\Client\Exception\KcoreUnavailableException;

/**
 * Optional native crypto core: libkcore (KnishIO-Crypto-Core) through PHP FFI.
 *
 * The package bundles one library per platform under kcore/<target>/ (pinned by sha256 in
 * scripts/kcore-pins.json). Every method returns null when the library is not in use, when the input
 * is not one kcore handles, or when the C call rejects it; callers then run their pure-PHP or Node
 * helper path, so results never depend on whether the library is present.
 *
 * KNISHIO_KCORE: "auto" (default: use the library when it loads), "off" (never load it) or "require"
 * (throw KcoreUnavailableException when it cannot be loaded). KNISHIO_KCORE_LIB overrides the library
 * path. Both are read on first use. Loading needs ext-ffi and an ffi.enable setting that permits
 * FFI::cdef here: the default "preload" allows it in the CLI but not under php-fpm.
 *
 * @package WishKnish\KnishIO\Client\Libraries
 */
final class Kcore {

  public const ABI_VERSION = 1;

  private const CDEF = <<<'C'
int kcore_abi_version(void);
int kcore_shake256(const uint8_t *in, size_t inlen, uint8_t *out, size_t outlen);
int kcore_chains_hex(char *chunks, const int *counts, size_t n, int ways);
int kcore_wots_address(const char *key_hex2048, char *address_hex64);
int kcore_wots_address_v2(const char *key_hex8192, char *address_hex64);
int kcore_mlkem1024_keypair(const uint8_t seed[64], uint8_t pk[1568], uint8_t sk[3168]);
int kcore_mlkem1024_encaps(const uint8_t pk[1568], const uint8_t coins[32], uint8_t ct[1568], uint8_t ss[32]);
int kcore_mlkem1024_decaps(const uint8_t ct[1568], const uint8_t sk[3168], uint8_t ss[32]);
int kcore_mlkem768_keypair(const uint8_t seed[64], uint8_t pk[1184], uint8_t sk[2400]);
int kcore_mlkem768_encaps(const uint8_t pk[1184], const uint8_t coins[32], uint8_t ct[1088], uint8_t ss[32]);
int kcore_mlkem768_decaps(const uint8_t ct[1088], const uint8_t sk[2400], uint8_t ss[32]);
C;

  private const NAMES = [ 'linux' => 'libkcore.so', 'darwin' => 'libkcore.dylib', 'windows' => 'kcore.dll' ];

  /** (public key, secret key, ciphertext) sizes per ML-KEM parameter set. */
  private const MLKEM_SIZES = [ 1024 => [ 1568, 3168, 1568 ], 768 => [ 1184, 2400, 1088 ] ];
  private const SEED_BYTES = 64;
  private const COINS_BYTES = 32;
  private const SS_BYTES = 32;
  private const SHAKE_MAX = 1048576;

  /** Load result: null (not attempted), 'off', 'ok' or 'failed'. */
  private static ?string $state = null;
  private static ?FFI $ffi = null;
  private static ?string $path = null;
  private static ?string $reason = null;
  private static string $mode = 'auto';

  /**
   * True when libkcore is loaded. In "require" mode a failed load throws on every call.
   *
   * @throws KcoreUnavailableException
   */
  public static function available (): bool {
    if ( self::$state === null ) {
      self::load();
    }
    if ( self::$state === 'failed' && self::$mode === 'require' ) {
      throw new KcoreUnavailableException( 'libkcore unavailable: ' . self::$reason . ' (' . ( self::$path ?? 'no path' ) . ')' );
    }
    return self::$state === 'ok';
  }

  public static function libraryPath (): ?string {
    return self::available() ? self::$path : null;
  }

  /**
   * The bundled library's target for this platform (linux-x64-gnu, linux-arm64-musl,
   * darwin-universal, windows-x64, ...), or null when the package carries none for it.
   */
  public static function target (): ?string {
    $machine = strtolower( php_uname( 'm' ) );
    $arch = match ( true ) {
      in_array( $machine, [ 'x86_64', 'amd64' ], true ) => 'x64',
      in_array( $machine, [ 'aarch64', 'arm64' ], true ) => 'arm64',
      default => null,
    };
    return match ( PHP_OS_FAMILY ) {
      'Linux' => $arch === null ? null : 'linux-' . $arch . '-' . ( glob( '/lib/ld-musl-*' ) ? 'musl' : 'gnu' ),
      'Darwin' => 'darwin-universal',
      'Windows' => $arch === 'x64' ? 'windows-x64' : null,
      default => null,
    };
  }

  /**
   * Forgets the load result so the next call re-reads the environment.
   *
   * @internal tests only
   */
  public static function reset (): void {
    self::$state = self::$ffi = self::$path = self::$reason = null;
    self::$mode = 'auto';
  }

  /**
   * SHAKE256(data), length bytes (1..1048576).
   */
  public static function shake256 ( string $data, int $length ): ?string {
    if ( $length < 1 || $length > self::SHAKE_MAX || !self::available() ) {
      return null;
    }
    $ffi = self::$ffi;
    $inlen = strlen( $data );
    $in = $ffi->new( 'uint8_t[' . max( $inlen, 1 ) . ']' );
    if ( $inlen > 0 ) {
      FFI::memcpy( $in, $data, $inlen );
    }
    $out = $ffi->new( "uint8_t[{$length}]" );
    if ( $ffi->kcore_shake256( $in, $inlen, $out, $length ) !== 0 ) {
      return null;
    }
    return FFI::string( $out, $length );
  }

  /**
   * Advances count($counts) WOTS+ chains of 128 hex characters each: chain i becomes
   * hex(SHAKE256(chain, 64 bytes)) applied $counts[i] times (4-lane Keccak).
   *
   * @param int[] $counts
   */
  public static function chainsHex ( string $chunks, array $counts ): ?string {
    $n = count( $counts );
    if ( $n < 1 || $n > 64 || strlen( $chunks ) !== 128 * $n || !self::isAscii( $chunks ) ) {
      return null;
    }
    foreach ( $counts as $c ) {
      if ( !is_int( $c ) || $c < 0 || $c > 64 ) {
        return null;
      }
    }
    if ( !self::available() ) {
      return null;
    }
    $ffi = self::$ffi;
    $size = 128 * $n;
    $buf = $ffi->new( "char[{$size}]" );
    FFI::memcpy( $buf, $chunks, $size );
    $cnt = $ffi->new( "int[{$n}]" );
    foreach ( array_values( $counts ) as $i => $c ) {
      $cnt[ $i ] = $c;
    }
    if ( $ffi->kcore_chains_hex( $buf, $cnt, $n, 4 ) !== 0 ) {
      return null;
    }
    return FFI::string( $buf, $size );
  }

  /**
   * WOTS+ address of a 2048-hex (16-chain, OTS v1) key.
   */
  public static function wotsAddress ( string $key ): ?string {
    return strlen( $key ) === 2048 ? self::address( 'kcore_wots_address', $key ) : null;
  }

  /**
   * WOTS+ address of an 8192-hex (64-chain, OTS v2) key.
   */
  public static function wotsAddressV2 ( string $key ): ?string {
    return strlen( $key ) === 8192 ? self::address( 'kcore_wots_address_v2', $key ) : null;
  }

  /**
   * Deterministic ML-KEM key pair from a 64-byte seed.
   *
   * @return array{publicKey: string, secretKey: string}|null raw bytes
   */
  public static function mlkemKeypair ( int $paramSet, string $seed ): ?array {
    if ( !isset( self::MLKEM_SIZES[ $paramSet ] ) || strlen( $seed ) !== self::SEED_BYTES || !self::available() ) {
      return null;
    }
    [ $pkLen, $skLen ] = self::MLKEM_SIZES[ $paramSet ];
    $ffi = self::$ffi;
    $seedBuf = $ffi->new( 'uint8_t[' . self::SEED_BYTES . ']' );
    $pk = $ffi->new( "uint8_t[{$pkLen}]" );
    $sk = $ffi->new( "uint8_t[{$skLen}]" );
    try {
      FFI::memcpy( $seedBuf, $seed, self::SEED_BYTES );
      $fn = "kcore_mlkem{$paramSet}_keypair";
      if ( $ffi->$fn( $seedBuf, $pk, $sk ) !== 0 ) {
        return null;
      }
      return [ 'publicKey' => FFI::string( $pk, $pkLen ), 'secretKey' => FFI::string( $sk, $skLen ) ];
    }
    finally {
      FFI::memset( $seedBuf, 0, self::SEED_BYTES );
      FFI::memset( $sk, 0, $skLen );
    }
  }

  /**
   * Encapsulates to a public key; the parameter set follows the key length (1568: 1024, 1184: 768).
   *
   * @return array{ciphertext: string, sharedSecret: string}|null raw bytes
   */
  public static function mlkemEncaps ( string $publicKey ): ?array {
    $paramSet = self::paramSetFor( 0, strlen( $publicKey ) );
    if ( $paramSet === null || !self::available() ) {
      return null;
    }
    [ $pkLen, , $ctLen ] = self::MLKEM_SIZES[ $paramSet ];
    $ffi = self::$ffi;
    $pk = $ffi->new( "uint8_t[{$pkLen}]" );
    FFI::memcpy( $pk, $publicKey, $pkLen );
    // Fresh coins on every call: repeating them against one key repeats ct and ss.
    $coins = $ffi->new( 'uint8_t[' . self::COINS_BYTES . ']' );
    $ct = $ffi->new( "uint8_t[{$ctLen}]" );
    $ss = $ffi->new( 'uint8_t[' . self::SS_BYTES . ']' );
    try {
      FFI::memcpy( $coins, random_bytes( self::COINS_BYTES ), self::COINS_BYTES );
      $fn = "kcore_mlkem{$paramSet}_encaps";
      if ( $ffi->$fn( $pk, $coins, $ct, $ss ) !== 0 ) {
        return null;
      }
      return [ 'ciphertext' => FFI::string( $ct, $ctLen ), 'sharedSecret' => FFI::string( $ss, self::SS_BYTES ) ];
    }
    finally {
      FFI::memset( $coins, 0, self::COINS_BYTES );
      FFI::memset( $ss, 0, self::SS_BYTES );
    }
  }

  /**
   * Decapsulates; the parameter set follows the (ciphertext, secret key) lengths.
   *
   * @return string|null the 32-byte shared secret
   */
  public static function mlkemDecaps ( string $ciphertext, string $secretKey ): ?string {
    $paramSet = self::paramSetFor( 2, strlen( $ciphertext ) );
    if ( $paramSet === null || strlen( $secretKey ) !== self::MLKEM_SIZES[ $paramSet ][ 1 ] || !self::available() ) {
      return null;
    }
    [ , $skLen, $ctLen ] = self::MLKEM_SIZES[ $paramSet ];
    $ffi = self::$ffi;
    $ct = $ffi->new( "uint8_t[{$ctLen}]" );
    FFI::memcpy( $ct, $ciphertext, $ctLen );
    $sk = $ffi->new( "uint8_t[{$skLen}]" );
    $ss = $ffi->new( 'uint8_t[' . self::SS_BYTES . ']' );
    try {
      FFI::memcpy( $sk, $secretKey, $skLen );
      $fn = "kcore_mlkem{$paramSet}_decaps";
      if ( $ffi->$fn( $ct, $sk, $ss ) !== 0 ) {
        return null;
      }
      return FFI::string( $ss, self::SS_BYTES );
    }
    finally {
      FFI::memset( $sk, 0, $skLen );
      FFI::memset( $ss, 0, self::SS_BYTES );
    }
  }

  private static function address ( string $function, string $key ): ?string {
    if ( !self::isAscii( $key ) || !self::available() ) {
      return null;
    }
    $ffi = self::$ffi;
    $len = strlen( $key );
    $in = $ffi->new( 'char[' . ( $len + 1 ) . ']' );
    FFI::memcpy( $in, $key, $len );
    $out = $ffi->new( 'char[64]' );
    if ( $ffi->$function( $in, $out ) !== 0 ) {
      return null;
    }
    return FFI::string( $out, 64 );
  }

  /**
   * The parameter set whose size at $index (0: public key, 2: ciphertext) is $length.
   */
  private static function paramSetFor ( int $index, int $length ): ?int {
    foreach ( self::MLKEM_SIZES as $set => $sizes ) {
      if ( $sizes[ $index ] === $length ) {
        return $set;
      }
    }
    return null;
  }

  private static function isAscii ( string $s ): bool {
    return !preg_match( '/[^\x00-\x7F]/', $s );
  }

  private static function load (): void {
    $mode = strtolower( getenv( 'KNISHIO_KCORE' ) ?: 'auto' );
    if ( !in_array( $mode, [ 'auto', 'off', 'require' ], true ) ) {
      throw new InvalidArgumentException( "KNISHIO_KCORE must be auto, off or require, got '{$mode}'" );
    }
    self::$mode = $mode;
    if ( $mode === 'off' ) {
      self::$state = 'off';
      return;
    }
    self::$reason = self::open();
    self::$state = self::$reason === null ? 'ok' : 'failed';
  }

  /**
   * Opens the library into self::$ffi; returns null on success or the failure reason.
   */
  private static function open (): ?string {
    $override = getenv( 'KNISHIO_KCORE_LIB' );
    if ( $override !== false && $override !== '' ) {
      self::$path = $override;
    }
    else {
      $target = self::target();
      if ( $target === null ) {
        return 'no kcore target for ' . PHP_OS_FAMILY . '/' . php_uname( 'm' );
      }
      $os = substr( $target, 0, strpos( $target, '-' ) );
      self::$path = dirname( __DIR__, 2 ) . "/kcore/{$target}/" . self::NAMES[ $os ];
    }
    if ( !extension_loaded( 'ffi' ) ) {
      return 'ext-ffi is not loaded';
    }
    if ( !is_file( self::$path ) ) {
      return 'library file not found';
    }
    try {
      $ffi = FFI::cdef( self::CDEF, self::$path );
      $abi = $ffi->kcore_abi_version();
    }
    catch ( Throwable $e ) {
      return 'FFI::cdef failed: ' . $e->getMessage();
    }
    if ( $abi !== self::ABI_VERSION ) {
      return "ABI version {$abi}, expected " . self::ABI_VERSION;
    }
    self::$ffi = $ffi;
    return null;
  }
}
