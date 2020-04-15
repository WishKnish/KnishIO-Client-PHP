<?php

namespace WishKnish\KnishIO\HttpClient;

use GuzzleHttp\ClientInterface;

/**
 * Interface HttpClientInterface
 * @package WishKnish\KnishIO\HttpClient
 */
interface HttpClientInterface extends ClientInterface {

	/**
	 * @param $url
	 * @return mixed
	 */
	public function url ();

	/**
	 * @param $url
	 * @return mixed
	 */
	public function setUrl ( $url );


	/**
	 * @param $authToken
	 * @return mixed
	 */
	public function setAuthToken ( $authToken );


	/**
	 * @return mixed
	 */
	public function getAuthToken ();


}
