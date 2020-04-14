<?php

namespace WishKnish\KnishIO\HttpClient;


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


	protected $request;

	/**
	 * @var string
	 */
	private $secret;

	/**
	 * @var array
	 */
	private $pendingRequest = [];


	/**
	 *
	 */
	public static function handler () {

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
	}


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
			'handler'     => static::handler(),
			'headers'     => [
				'User-Agent' => 'KnishIO/0.1',
				'Accept'     => 'application/json',
			],
			'x_auth_token_getter' => static function () {

				// Set auth token
				$this->setAuthToken( $this->authentication( $this->getSecret() )->payload() );

				// Return an auth token
				return $this->xAuthToken;

			},
		], $config);

		// Guzzle constructor
		parent::__construct($config);
	}


	/**
	 * @return mixed
	 */
	public function url ()
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


	public function send(RequestInterface $request, array $options = [])
	{
		// Save a request
		$this->request = $request;

		return parent::send($request, $options);
	}


	/**
	 * @param string $name
	 * @param callable $variables
	 * @return bool
	 */
	protected function addPending( $name, array $variables = [] )
	{
		if ( isset( $this->pendingRequest[ $name ] ) ) {

			unset ( $this->pendingRequest[ $name ] );

			return true;
		}

		$this->pending( $name, function () use ( $name, $variables ) { return $this->{ $name }( ...$variables ); } );

		return false;
	}

	/**
	 * @param string $name
	 * @param callable $closure
	 */
	protected function pending ( $name, callable $closure )
	{
		$this->pendingRequest[ $name ] = $closure;
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


}
