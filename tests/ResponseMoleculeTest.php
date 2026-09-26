<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use JsonException;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;

/**
 * A rejected ProposeMolecule carries `payload: null`. Initialising the response must not hand
 * that null to json_decode (deprecated since PHP 8.1) and must leave payload() null.
 */
class ResponseMoleculeTest extends TestCase {

  /**
   * @param string $json
   *
   * @return array{0: ResponseMolecule, 1: list<string>}
   * @throws JsonException
   */
  private function initCapturingDeprecations ( string $json ): array {
    $deprecations = [];
    set_error_handler( static function ( int $errno, string $errstr ) use ( &$deprecations ): bool {
      $deprecations[] = $errstr;
      return true;
    }, E_DEPRECATED | E_USER_DEPRECATED );

    try {
      $response = new ResponseMolecule( null, $json );
    }
    finally {
      restore_error_handler();
    }

    return [ $response, $deprecations ];
  }

  /**
   * @throws JsonException
   */
  public function testRejectedResponseWithNullPayloadInitialisesWithoutDeprecation (): void {
    [ $response, $deprecations ] = $this->initCapturingDeprecations( json_encode( [
      'data' => [
        'ProposeMolecule' => [
          'molecularHash' => 'abc',
          'status' => 'rejected',
          'reason' => 'Signer address mismatch',
          'payload' => null,
        ],
      ],
    ], JSON_THROW_ON_ERROR ) );

    $this->assertSame( [], $deprecations );
    $this->assertNull( $response->payload() );
    $this->assertFalse( $response->success() );
    $this->assertSame( 'Signer address mismatch', $response->reason() );
  }

  /**
   * @throws JsonException
   */
  public function testAcceptedResponseDecodesStringPayload (): void {
    [ $response, $deprecations ] = $this->initCapturingDeprecations( json_encode( [
      'data' => [
        'ProposeMolecule' => [
          'molecularHash' => 'abc',
          'status' => 'accepted',
          'payload' => '{"token":"jwt","expiresAt":1}',
        ],
      ],
    ], JSON_THROW_ON_ERROR ) );

    $this->assertSame( [], $deprecations );
    $this->assertSame( [ 'token' => 'jwt', 'expiresAt' => 1 ], $response->payload() );
    $this->assertTrue( $response->success() );
  }
}
