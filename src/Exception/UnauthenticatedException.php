<?php
namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

/**
 * Class UnauthenticatedException
 * @package WishKnish\KnishIO\Client\Exception
 */
class UnauthenticatedException extends BaseException
{
    /**
     * UnauthenticatedException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct ( $message = 'Unauthenticated.', $code = 2, Throwable $previous = null )
    {
        parent::__construct( $message, $code, $previous );
    }
}
