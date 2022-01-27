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

namespace WishKnish\KnishIO\Client\Response;

use JsonException;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Exception\UnauthenticatedException;
use WishKnish\KnishIO\Client\Query\Query;

/**
 * Class Response
 * @package WishKnish\KnishIO\Client\Query
 */
class Response {
  /**
   * @var Query|null
   */
  protected ?Query $query;

  /**
   * @var array|null
   */
  protected ?array $response;

  /**
   * @var string
   */
  protected string $origin_response;

  /**
   * @var mixed
   */
  protected mixed $payload;

  /**
   * @var string
   */
  protected string $dataKey;

  /**
   * Response constructor.
   *
   * @param Query|null $query
   * @param string $json
   * @param string|null $dataKey
   *
   * @throws JsonException
   */
  public function __construct ( ?Query $query, string $json, string $dataKey = null ) {
    // Set a query
    $this->query = $query;

    // Origin response
    $this->origin_response = $json;

    // Json decode
    $this->response = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );

    // Set datakey from
    if ( $dataKey !== null ) {
      $this->dataKey = $dataKey;
    }

    // Catch exceptions
    if ( array_has( $this->response, 'exception' ) ) {

      // Exception error
      $message = array_get( $this->response, 'message' );

      // Custom exceptions
      if ( stripos( $message, 'Unauthenticated' ) !== false ) {
        throw new UnauthenticatedException ( $message );
      }

      // Default exception
      throw new InvalidResponseException( $message );
    }

    // No-json response - error
    if ( $this->response === null ) {
      throw new InvalidResponseException();
    }

    $this->init();
  }

  public function init (): void {

  }

  /**
   * Get a response
   *
   * @return mixed
   */
  public function data (): mixed {
    // For the root class
    if ( !$this->dataKey ) {
      return $this->response;
    }

    // Check key & return custom data from the response
    if ( !array_has( $this->response, $this->dataKey ) ) {
      throw new InvalidResponseException();
    }

    return array_get( $this->response, $this->dataKey );
  }

  /**
   * @return array|null
   */
  public function response (): ?array {
    return $this->response;
  }

  /**
   * Get a payload
   *
   * @return mixed
   */
  public function payload (): mixed {
    return null;
  }

  /**
   * @return Query|null
   */
  public function query (): ?Query {
    return $this->query;
  }

}
