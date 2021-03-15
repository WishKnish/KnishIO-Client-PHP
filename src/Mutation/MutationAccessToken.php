<?php
namespace WishKnish\KnishIO\Client\Mutation;

use WishKnish\KnishIO\Client\Response\ResponseAccessToken;


class MutationAccessToken extends Mutation
{
  // Query
  protected static $default_query = 'mutation( $cellSlug: String ) { AccessToken( cellSlug: $cellSlug ) @fields }';

  // Fields
  protected $fields = [
    'token',
    'time',
  ];

  /**
   * Create a response
   *
   * @param $response
   *
   * @return ResponseAccessToken
   */
  public function createResponse ( $response ): ResponseAccessToken
  {
    return new ResponseAccessToken( $this, $response );
  }
}
