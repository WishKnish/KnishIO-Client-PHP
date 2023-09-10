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
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Exception\ResponseJsonException;
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
    protected string $originResponse;

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
     * @throws KnishIOException
     */
    public function __construct ( ?Query $query, string $json, string $dataKey = null ) {
        // Set a query
        $this->query = $query;

        // Origin response
        $this->originResponse = $json;

        if ( $json ) {
            // Json decode
            try {
                $this->response = json_decode( $json, true, 512, JSON_THROW_ON_ERROR );
            }
            catch ( JsonException $e ) {
                throw new ResponseJsonException( 'Json exception thrown when decoding response: ' . $e->getMessage() . '; ' . $json, $json );
            }
        }
        else {
            $this->response = null;
        }

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
                throw new UnauthenticatedException ( $message, $this->response );
            }

            // Default exception
            throw new InvalidResponseException( $message, $this->response );
        }

        // No-json response - error
        if ( $this->response === null ) {
            throw new InvalidResponseException( 'No GraphQL response returned.', $query );
        }

        $this->init();
    }

    /**
     * Initialization
     */
    public function init (): void {

    }

    /**
     * Get a response
     *
     * @return mixed
     * @throws KnishIOException
     */
    public function data (): mixed {

        // For the root class
        if ( !$this->dataKey ) {
            return $this->response;
        }

        $payload = array_get( $this->response, $this->dataKey );

        // Check key & return custom data from the response
        if ( array_key_exists( 'errors', $this->response ) ) {

            $messages = '';
            foreach ( $this->response[ 'errors' ] as $error ) {
                $messages .= json_encode( $error ) . '; ';
            }

            throw new InvalidResponseException( 'GraphQL call returned ' . count( $this->response[ 'errors' ] ) . ' errors: ' . $messages, $this->response );
        }

        return $payload;
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
    public function getPayload (): mixed {
        return null;
    }

    /**
     * @return Query|null
     */
    public function query (): ?Query {
        return $this->query;
    }

}
