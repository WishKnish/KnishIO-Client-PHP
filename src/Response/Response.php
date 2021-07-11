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

use Illuminate\Support\Facades\Log;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Exception\UnauthenticatedException;
use WishKnish\KnishIO\Client\Query\Query;

/**
 * Class Response
 * @package WishKnish\KnishIO\Client\Query
 */
class Response
{
    /**
     * @var Query
     */
	protected $query;

    /**
     * @var array|null
     */
	protected $response;

	/**
	 * @var
	 */
	protected $payload;

    /**
     * @var string
     */
	protected $dataKey;

  /**
   * Response constructor.
   *
   * @param Query|null $query
   * @param string $json
   * @param string|null $dataKey
   */
	public function __construct ( ?Query $query, string $json, string $dataKey = null )
	{
		// Set a query
		$this->query = $query;

		// Origin response
		$this->origin_response = $json;

		// Json decode
		$this->response = json_decode( $json, true );

		// Set datakey from
		if ( $dataKey !== null ) {
		  $this->dataKey = $dataKey;
    }

    // Catch exceptions
    if (array_has ($this->response, 'exception') ) {

      // Exception error
      $message = array_get($this->response, 'message');

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

		$this->init ();
	}


	/**
	 *
	 */
	public function init () {

	}


	/**
	 * Get a response
	 *
	 * @return mixed
	 */
	public function data ()
    {
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
	 * @return mixed
	 */
	public function response ()
    {
		return $this->response;
	}


	/**
	 * Get a payload
	 *
	 * @return
	 */
	public function payload ()
    {
		return null;
	}


	/**
	 * @return Query
	 */
	public function query ()
    {
		return $this->query;
	}

}
