<?php

namespace WishKnish\KnishIO\Client\HttpClient;

use GuzzleHttp\ClientInterface;

/**
 * Interface HttpClientInterface
 * @package WishKnish\KnishIO\HttpClient
 */
interface HttpClientInterface extends ClientInterface {

	/**
	 * @return string
	 */
	public function getUrl (): string;

  /**
   * @param string $url
   *
   * @return mixed
   */
	public function setUrl ( string $url ): void;


	/**
	 * @param string $authToken
	 * @return void
	 */
	public function setAuthToken ( string $authToken ): void;


	/**
	 * @return string|null
	 */
	public function getAuthToken (): ?string ;


}
