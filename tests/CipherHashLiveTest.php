<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;

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
 * Gated on CIPHERHASH_TEST_URL alone: the tests skip only when it is unset. When it is set, an
 * unreachable validator fails the test rather than skipping it. Run live:
 *   CIPHERHASH_TEST_URL=https://testnet.knish.io/graphql vendor/bin/phpunit --filter CipherHashLiveTest
 */
class CipherHashLiveTest extends TestCase {

  /**
   * The validator endpoint under test; skips when CIPHERHASH_TEST_URL is unset.
   */
  private function serverUrl (): string {
    $url = getenv( 'CIPHERHASH_TEST_URL' );
    if ( !$url ) {
      $this->markTestSkipped( 'set CIPHERHASH_TEST_URL to run the live CipherHash test' );
    }
    return $url;
  }

  /**
   * The encrypted CipherHash round-trip must return the SAME balance data as the plaintext path.
   */
  public function testEncryptedCipherHashRoundTripMatchesPlaintext (): void {
    $url = $this->serverUrl();

    $secret = Crypto::generateSecret();

    // ONE session, transport toggled on it — the queried balance wallet stays fixed. (A fresh
    // second auth would rotate the USER remainder via ContinuID → a different address/position/
    // pubkey: correct protocol behaviour, NOT a transport bug, so it must not be the variable.)
    //
    // The session authenticates PLAINTEXT on purpose. The AUTH wallet's ML-KEM pubkey is conveyed
    // as a signed walletPubkey U-atom meta regardless of $encrypt (KnishIOClient.php:1508-1517),
    // and the validator's CipherHash handler needs only that key — so a plaintext-authenticated
    // session still speaks the encrypted transport. Authenticating with encrypt=true instead would
    // make the plaintext baseline leg below a silent downgrade, which the validator rejects when
    // ENFORCE_ENCRYPTED_TRANSPORT is at its secure default.
    $client = new KnishIOClient( $url );
    $param = getenv( 'CIPHERHASH_MLKEM_PARAMETER_SET' );
    if ( $param ) {
      $client->setMlKemParameterSet( (int)$param );
    }
    $client->setCellSlug( 'public' );   // the active dev cell (TESTCELL is inactive there)
    $client->requestAuthToken( $secret, 'public', false );

    // Encrypted round-trip: the validator ML-KEM-decrypts the request, executes it, and encrypts
    // the response back to the client's ML-KEM pubkey; the client decrypts it.
    $client->switchEncryption( true );
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

  /**
   * Live coverage of the enforcement path: extract_encrypt_flag → auth_tokens.encrypted →
   * requires_encrypted_transport. Also proves this SDK's signed `encrypt` meta literal is the one
   * the validator honours: a session that authenticated with encrypt=true must not be able to
   * fall back to plaintext.
   */
  public function testEncryptedSessionIsRefusedWhenItDropsToPlaintext (): void {
    $url = $this->serverUrl();

    $secret = Crypto::generateSecret();
    $client = new KnishIOClient( $url );
    $param = getenv( 'CIPHERHASH_MLKEM_PARAMETER_SET' );
    if ( $param ) {
      $client->setMlKemParameterSet( (int)$param );
    }
    $client->setCellSlug( 'public' );
    $client->requestAuthToken( $secret, 'public', true );

    // The encrypted transport still works for this session.
    $this->assertNotNull( $client->queryBalance( 'USER' )->payload() );

    // Dropping to plaintext on the same session is the silent downgrade the validator refuses;
    // Response::data() raises InvalidResponseException carrying the encoded GraphQL errors.
    $client->switchEncryption( false );
    $this->expectException( InvalidResponseException::class );
    $this->expectExceptionMessageMatches( '/CipherHash encrypted transport/' );
    $client->queryBalance( 'USER' )->payload();
  }
}
