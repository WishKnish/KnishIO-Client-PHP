<?php
namespace WishKnish\KnishIO\Client\Middleware;

use GuzzleHttp\RetryMiddleware;
use Psr\Http\Message\RequestInterface;
use Closure;

/**
 * Class RetryMiddleware
 * @package WishKnish\KnishIO\Client\Middleware
 */
class RetryGuzzleMiddleware extends RetryMiddleware
{
    /**
     * @param RequestInterface $request
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function __invoke( RequestInterface $request, array $options )
    {
        if ( !isset( $options[ 'retries' ] ) ) {
            $options[ 'retries' ] = 0;
        }

        if ( $options[ 'retries' ] > 0 && isset( $options[ 'auth_token_header' ] ) && $options[ 'auth_token_header' ] instanceof Closure ) {
            $request = $options[ 'auth_token_header' ]( $request );
        }

        return parent::__invoke( $request, $options );
    }
}
