<?php

namespace WishKnish\KnishIO\Client\HttpClient;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class HttpClient
 * @package WishKnish\KnishIO\HttpClient
 */
class HttpClient extends \GuzzleHttp\Client implements HttpClientInterface {


	/**
	 * @var string
	 */
	private $xAuthToken;

	protected $url;

	/**
	 * HttpClient constructor.
     * @param string $url
	 * @param array $config
	 */
	public function __construct( $url, array $config = [] )
	{
		$this->setUrl( $url );

		// Merge config
		$config = array_merge ([
			'base_uri'    => $url,
			'verify'      => false,
			'http_errors' => false,
			'headers'     => [
				'User-Agent' => 'KnishIO/0.1',
				'Accept'     => 'application/json',
			],
		], $config);

		// Guzzle constructor
		parent::__construct($config);
	}


	/**
	 * @return mixed
	 */
	public function getUrl ()
	{
		return $this->url;
	}


	/**
	 * @param $url
	 * @return mixed|void
	 */
	public function setUrl ($url) {
		$this->url = $url;
	}


	/**
	 * @param string $authToken
	 */
	public function setAuthToken ( $authToken )
	{
		$this->xAuthToken = $authToken;
	}


	/**
	 * @return string|null
	 */
	public function getAuthToken ()
	{
		return $this->xAuthToken;
	}


	/**
	 * @param RequestInterface $request
	 * @param array $options
	 * @return ResponseInterface
	 */
	public function send (RequestInterface $request, array $options = [])
	{
		// Add x-auth-token header
		$options['headers'] = array_merge (array_get($options, 'headers', []), [
			'X-Auth-Token' => $this->getAuthToken(),
		]);

		// Send a request
		return parent::send($request, $options);
	}

}
