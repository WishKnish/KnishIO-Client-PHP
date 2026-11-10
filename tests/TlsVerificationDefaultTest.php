<?php
/*
 * The HTTP client verifies the server's TLS certificate unless the caller opts out,
 * either through KnishIOClient's $insecureTls or a Guzzle `verify => false` option.
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\KnishIOClient;

class TlsVerificationDefaultTest extends TestCase {

  private const URI = 'https://node.example/graphql';

  public function testCertificatesAreVerifiedByDefault (): void {
    $this->assertTrue( ( new HttpClient( self::URI ) )->verifiesTls() );
    $this->assertTrue( ( new KnishIOClient( self::URI ) )->client()->verifiesTls() );
  }

  public function testInsecureTlsIsAnExplicitOptIn (): void {
    $this->assertFalse( ( new KnishIOClient( self::URI, insecureTls: true ) )->client()->verifiesTls() );
  }

  public function testCallerConfigCanDisableVerification (): void {
    $this->assertFalse( ( new HttpClient( self::URI, [ 'verify' => false ] ) )->verifiesTls() );
  }

}
