<?php
/**
 * Checks this platform's bundled libkcore through the SDK, against the cross-platform vectors.
 *
 * usage: php scripts/kcore-check.php --vectors <cross-platform-test-vectors.json> --expect-target <target>
 *
 * Runs with KNISHIO_KCORE=require and checks: the detected target and the library path, the WOTS+
 * addresses (Kcore::wotsAddress and Wallet), the ML-KEM-1024 and ML-KEM-768 keygen KATs and
 * encaps/decaps round trips, the SDK's ML-KEM keygen and decrypt vectors, and a WOTS+ chain walk.
 * The references for the chain walk and the KAT seeds come from the pure-PHP SHA3 sponge.
 *
 * Prints "KCORE_PHP ok target=<t> lib=<path>" or "KCORE_PHP FAIL <step>" and exits 1.
 */

use WishKnish\KnishIO\Client\Libraries\Crypto\SHA3\Sponge;
use WishKnish\KnishIO\Client\Libraries\Kcore;
use WishKnish\KnishIO\Client\Libraries\PostQuantumCrypto;
use WishKnish\KnishIO\Client\Wallet;

require dirname( __DIR__ ) . '/vendor/autoload.php';

function fail ( string $step ): never {
  echo "KCORE_PHP FAIL {$step}\n";
  exit( 1 );
}

function check ( bool $cond, string $step ): void {
  if ( !$cond ) {
    fail( $step );
  }
}

function shake ( string $data, int $length ): string {
  return Sponge::init( Sponge::SHAKE256 )->absorb( $data )->squeeze( $length );
}

$opts = getopt( '', [ 'vectors:', 'expect-target:' ] );
if ( !isset( $opts[ 'vectors' ], $opts[ 'expect-target' ] ) ) {
  fwrite( STDERR, "usage: php scripts/kcore-check.php --vectors <file> --expect-target <target>\n" );
  exit( 2 );
}
putenv( 'KNISHIO_KCORE=require' );
$vectors = json_decode( file_get_contents( $opts[ 'vectors' ] ), true, 512, JSON_THROW_ON_ERROR )[ 'vectors' ];
$target = $opts[ 'expect-target' ];

try {
  check( Kcore::target() === $target, 'target ' . var_export( Kcore::target(), true ) . ", expected {$target}" );
  check( Kcore::available(), 'available' );
  $lib = Kcore::libraryPath();
  $name = str_starts_with( $target, 'windows' ) ? 'kcore.dll' : ( str_starts_with( $target, 'darwin' ) ? 'libkcore.dylib' : 'libkcore.so' );
  check( $lib !== null && str_ends_with( str_replace( '\\', '/', $lib ), "kcore/{$target}/{$name}" ), "library-path {$lib}" );
  check( PostQuantumCrypto::backend() === 'kcore', 'backend ' . PostQuantumCrypto::backend() );

  foreach ( $vectors[ 'wallet_generation' ][ 'tests' ] as $t ) {
    $key = Wallet::generateKey( $t[ 'secret' ], $t[ 'token' ], $t[ 'position' ] );
    check( Kcore::wotsAddress( $key ) === $t[ 'expectedAddress' ], "wots_address {$t['name']}" );
    check( ( new Wallet( $t[ 'secret' ], $t[ 'token' ], $t[ 'position' ] ) )->address === $t[ 'expectedAddress' ], "sdk address {$t['name']}" );
  }

  foreach ( [ 1024, 768 ] as $set ) {
    $kg = $vectors[ "mlkem{$set}" ][ 'keygen' ];
    $key = Wallet::generateKey( $kg[ 'secret' ], $kg[ 'token' ], $kg[ 'position' ] );
    $pair = Kcore::mlkemKeypair( $set, shake( $key, 64 ) );
    check( $pair !== null && base64_encode( $pair[ 'publicKey' ] ) === $kg[ 'expectedPubkey' ], "mlkem{$set} keygen KAT" );
    $enc = Kcore::mlkemEncaps( $pair[ 'publicKey' ] );
    check( $enc !== null && Kcore::mlkemDecaps( $enc[ 'ciphertext' ], $pair[ 'secretKey' ] ) === $enc[ 'sharedSecret' ], "mlkem{$set} round trip" );

    $wallet = new Wallet( $kg[ 'secret' ], $kg[ 'token' ], $kg[ 'position' ], mlKemParameterSet: $set );
    check( $wallet->pubkey === $kg[ 'expectedPubkey' ], "sdk mlkem{$set} keygen" );
    $d = $vectors[ "mlkem{$set}" ][ 'decrypt' ];
    $wallet = new Wallet( $d[ 'secret' ], $d[ 'token' ], $d[ 'position' ], mlKemParameterSet: $set );
    $plain = $wallet->decryptMessageML( [ 'cipherText' => $d[ 'cipherText' ], 'encryptedMessage' => $d[ 'encryptedMessage' ] ] );
    check( $plain === $d[ 'expectedPlaintext' ], "sdk mlkem{$set} decrypt" );
  }

  $k = bin2hex( shake( 'kcore-wheel-check', 1024 ) );
  $reference = '';
  foreach ( str_split( $k, 128 ) as $i => $chunk ) {
    for ( $round = 0; $round < $i; $round++ ) {
      $chunk = bin2hex( shake( $chunk, 64 ) );
    }
    $reference .= $chunk;
  }
  check( Kcore::chainsHex( $k, range( 0, 15 ) ) === $reference, 'chains_hex' );
}
catch ( Throwable $e ) {
  fail( get_class( $e ) . ': ' . $e->getMessage() );
}

echo "KCORE_PHP ok target={$target} lib={$lib}\n";
