<?php

namespace WishKnish\KnishIO\Client\HttpClient;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WishKnish\KnishIO\Client\Middleware\RetryGuzzleMiddleware;

/**
 * Class HttpClient
 * @package WishKnish\KnishIO\HttpClient
 */
class HttpClient extends \GuzzleHttp\Client implements HttpClientInterface {


	/**
	 * @var string
	 */
	private $xAuthToken;



	/**
	 *
	 */
	/*public static function handler () {

		// Create a handler
		$handler = HandlerStack::create( new CurlMultiHandler() );

		// Push RetryGuzzleMiddleware to retry request if: this is the first attempt & response = 401 Unauthorized
		$handler->push( function ( callable $handler )
		{
			return new RetryGuzzleMiddleware(

				// Returns TRUE if the request is to be retried
				function ( $retries, RequestInterface $request, ResponseInterface $response = null, RequestException $exception = null ) 				{
					return $retries === 0 && $response !== null && $response->getStatusCode() === 401;
				},

				$handler,

				null
			);
		} );
	}*/


	/**
	 * HttpClient constructor.
	 * @param array $config
	 */
	public function __construct(string $url, array $config = [])
	{
		$this->setUrl($url);

		// Merge config
		$config = array_merge ([
			'base_uri'    => $url,
			'verify'      => false,
			'http_errors' => false,
			// 'handler'     => static::handler(),
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
	 * @throws Exception
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
		// Add
		$request->withHeader( 'X-Auth-Token', $this->getAuthToken() );

		// Send a request
		return parent::send($request, $options);
	}


}
