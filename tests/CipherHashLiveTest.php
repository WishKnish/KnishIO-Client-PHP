<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;

/**
 * Live ML-KEM768 `CipherHash` encrypted-transport round-trip against a running validator
 * (PQ-transport Phase E, cycle 165 — PHP).
 *
 * End-to-end: the client authenticates (conveying its AUTH source wallet's ML-KEM public key via
 * a signed `walletPubkey` U-atom meta), then issues an encrypted `queryBalance` — the validator
 * ML-KEM-decrypts the request, executes it, and encrypts the response back to the client's ML-KEM
 * pubkey, which the client decrypts. The transport must be TRANSPARENT, so we assert the encrypted
 * result's DATA equals a plaintext baseline (not merely that both report success()).
 *
 * Gated: skips cleanly when no validator is reachable. Run live against the dev validator:
 *   CIPHERHASH_TEST_URL=http://localhost:8081/graphql vendor/bin/phpunit --filter CipherHashLiveTest
 */
class CipherHashLiveTest extends TestCase {

  private function serverUrl (): string {
    return getenv( 'CIPHERHASH_TEST_URL' ) ?: 'http://localhost:8081/graphql';
  }

  /**
   * Skip when no KnishIO validator answers at the endpoint (mirrors KnishIOClientTest's gate).
   */
  private function requireValidator ( string $url ): void {
    $ch = curl_init( $url );
    curl_setopt_array( $ch, [
      CURLOPT_NOBODY         => true,
      CURLOPT_CONNECTTIMEOUT => 2,
      CURLOPT_TIMEOUT        => 3,
      CURLOPT_RETURNTRANSFER => true,
    ] );
    curl_exec( $ch );
    $errno = curl_errno( $ch );
    curl_close( $ch );
    if ( $errno !== 0 ) {
      $this->markTestSkipped( "No validator reachable at {$url} (curl errno {$errno}) — skipping live CipherHash test" );
    }
  }

  /**
   * The encrypted CipherHash round-trip must return the SAME balance data as the plaintext path.
   */
  public function testEncryptedCipherHashRoundTripMatchesPlaintext (): void {
    $url = $this->serverUrl();
    $this->requireValidator( $url );

    $secret = Crypto::generateSecret();

    // ONE authenticated session (encrypt=true → conveys the AUTH wallet's ML-KEM pubkey as a
    // signed walletPubkey U-atom meta, so the validator can encrypt responses back to it). We vary
    // ONLY the transport on this SAME session — the queried balance wallet stays fixed. (A fresh
    // second auth would rotate the USER remainder via ContinuID → a different address/position/
    // pubkey: correct protocol behaviour, NOT a transport bug, so it must not be the variable.)
    $client = new KnishIOClient( $url );
    $client->setCellSlug( 'public' );   // the active dev cell (TESTCELL is inactive there)
    $client->requestAuthToken( $secret, 'public', true );

    // Encrypted round-trip: the validator ML-KEM-decrypts the request, executes it, and encrypts
    // the response back to the client's ML-KEM pubkey; the client decrypts it.
    $encResp = $client->queryBalance( 'USER' );

    // Plaintext baseline of the SAME wallet on the SAME authed session — only the transport differs.
    $client->switchEncryption( false );
    $plainResp = $client->queryBalance( 'USER' );

    // The PQ transport must be transparent: not just a non-error response, but the SAME data.
    // We assert on the decrypted PAYLOAD (a null payload would mean the decode silently dropped
    // the data — cf. the cycle-164 Kotlin @SerialName bug, which a success()-only check missed).
    $enc = $encResp->payload();
    $plain = $plainResp->payload();
    $this->assertNotNull( $enc, 'encrypted queryBalance payload must not be null — the transport must deliver data, not just decode to a non-error' );
    $this->assertNotNull( $plain, 'plaintext queryBalance payload must not be null' );

    // Same authed session → identical balance wallet → its deterministic identity fields match.
    $this->assertSame( $plain->address, $enc->address );
    $this->assertSame( $plain->position, $enc->position );
    $this->assertSame( $plain->pubkey, $enc->pubkey );
    $this->assertSame( $plain->token, $enc->token );
    $this->assertSame( $plain->bundle, $enc->bundle );
  }
}
