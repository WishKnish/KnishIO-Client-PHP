<?php

namespace WishKnish\KnishIO\Client\HttpClient;

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
	public function getUrl ();

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
