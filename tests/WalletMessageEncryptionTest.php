<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Exception\KcoreUnavailableException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Kcore;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Wallet::encryptMessage()/decryptMessage() are the ML-KEM + AES-GCM envelope of JS
 * Wallet.encryptMessage()/decryptMessage(), keyed by the wallet's ML-KEM `pubkey`; with
 * KNISHIO_KCORE=require and no loadable libkcore the failure propagates instead of reading as a
 * bad ciphertext.
 */
class WalletMessageEncryptionTest extends TestCase {

  private static string|false $outerMode;
  private static string|false $outerLib;

  public static function setUpBeforeClass (): void {
    self::$outerMode = getenv( 'KNISHIO_KCORE' );
    self::$outerLib = getenv( 'KNISHIO_KCORE_LIB' );
  }

  protected function tearDown (): void {
    putenv( self::$outerMode === false ? 'KNISHIO_KCORE' : 'KNISHIO_KCORE=' . self::$outerMode );
    putenv( self::$outerLib === false ? 'KNISHIO_KCORE_LIB' : 'KNISHIO_KCORE_LIB=' . self::$outerLib );
    Kcore::reset();
  }

  private static function wallets (): array {
    return [
      new Wallet( Crypto::generateSecret( 'wallet-message-sender', 2048 ), 'USER', str_repeat( '1a', 32 ) ),
      new Wallet( Crypto::generateSecret( 'wallet-message-recipient', 2048 ), 'USER', str_repeat( '2b', 32 ) ),
    ];
  }

  private static function breakKcore (): void {
    putenv( 'KNISHIO_KCORE=require' );
    putenv( 'KNISHIO_KCORE_LIB=/nonexistent/libkcore.so' );
    Kcore::reset();
  }

  public function testRoundTripWithTheWalletPubkey (): void {
    [ $sender, $recipient ] = self::wallets();
    $message = [ 'text' => 'hello/é', 'n' => 1 ];

    $envelope = $sender->encryptMessage( $message, $recipient->pubkey );

    $this->assertSame( [ 'cipherText', 'encryptedMessage' ], array_keys( $envelope ) );
    $this->assertSame( $message, $recipient->decryptMessage( $envelope ) );
    $this->assertNull( $sender->decryptMessage( $envelope ), 'a wallet the envelope is not addressed to cannot read it' );
  }

  public function testBinaryRoundTrip (): void {
    [ $sender, $recipient ] = self::wallets();
    $bytes = random_bytes( 64 );
    $this->assertSame( $bytes, $recipient->decryptBinary( $sender->encryptBinary( $bytes, $recipient->pubkey ) ) );
  }

  public function testMissingKcoreIsNotReadAsABadCiphertext (): void {
    [ $sender, $recipient ] = self::wallets();
    $envelope = $sender->encryptMessage( 'secret', $recipient->pubkey );

    self::breakKcore();
    $this->expectException( KcoreUnavailableException::class );
    $recipient->decryptMessage( $envelope );
  }

  public function testMissingKcorePropagatesFromEncryption (): void {
    [ $sender, $recipient ] = self::wallets();

    self::breakKcore();
    $this->expectException( KcoreUnavailableException::class );
    $sender->encryptMessage( 'secret', $recipient->pubkey );
  }

  public function testMissingKcorePropagatesFromBinaryDecryption (): void {
    [ $sender, $recipient ] = self::wallets();
    $envelope = $sender->encryptBinaryML( 'bytes', $recipient->pubkey );

    self::breakKcore();
    $this->expectException( KcoreUnavailableException::class );
    $recipient->decryptBinaryML( $envelope );
  }

  public function testMissingKcorePropagatesFromBinaryEncryption (): void {
    [ $sender, $recipient ] = self::wallets();

    self::breakKcore();
    $this->expectException( KcoreUnavailableException::class );
    $sender->encryptBinaryML( 'bytes', $recipient->pubkey );
  }
}
