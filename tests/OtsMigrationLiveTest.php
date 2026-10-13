<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Live OTS version migration against a running validator: an identity created by a client at OTS
 * version 1 moves to version 2 when a client set to version 2 signs in, and stays at version 2 when
 * a client at version 1 signs in afterwards.
 *
 * Gated on OTS_MIGRATION_TEST_URL (skips when unset):
 *   OTS_MIGRATION_TEST_URL=http://localhost:8081/graphql vendor/bin/phpunit --filter OtsMigrationLiveTest
 */
class OtsMigrationLiveTest extends TestCase {

  public function testClientAtOtsVersion2MovesVersion1IdentityToVersion2 (): void {
    $url = getenv( 'OTS_MIGRATION_TEST_URL' );
    if ( !$url ) {
      $this->markTestSkipped( 'set OTS_MIGRATION_TEST_URL to run the live OTS migration test' );
    }

    $secret = Crypto::generateSecret();
    $bundle = Crypto::generateBundleHash( $secret );
    $client = function ( int $otsVersion ) use ( $url, $secret ): KnishIOClient {
      $knishio = new KnishIOClient( $url, otsVersion: $otsVersion );
      $knishio->setCellSlug( 'public' );
      $knishio->requestAuthToken( $secret, 'public', false );
      return $knishio;
    };
    $head = function ( KnishIOClient $knishio ) use ( $bundle ): Wallet {
      $wallet = $knishio->queryContinuId( $bundle, 'USER' )->payload();
      $this->assertInstanceOf( Wallet::class, $wallet );
      return $wallet;
    };
    $version = static fn ( Wallet $wallet ): ?int => Wallet::detectOtsVersion( $secret, 'USER', $wallet->position, $wallet->address );

    // A client at version 1 creates the identity
    $first = $client( 1 );
    $h1 = $head( $first );
    $this->assertSame( 1, $version( $h1 ) );

    // A client at version 2 signs in from the version 1 head and leaves a version 2 head
    $second = $client( 2 );
    $h2 = $head( $second );
    $this->assertNotSame( $h1->position, $h2->position );
    $this->assertSame( 2, $version( $h2 ) );

    // The version 2 head signs a molecule
    $meta = $second->createMeta( 'ots-migration', 'ots-migration-' . bin2hex( random_bytes( 8 ) ), [ 'k' => 'v' ] );
    $this->assertSame( 'accepted', $meta->status() );
    $h3 = $head( $second );
    $this->assertNotSame( $h2->position, $h3->position );
    $this->assertSame( 2, $version( $h3 ) );

    // A client at version 1 signs in and the identity stays at version 2
    $third = $client( 1 );
    $h4 = $head( $third );
    $this->assertNotSame( $h3->position, $h4->position );
    $this->assertSame( 2, $version( $h4 ) );
  }
}
