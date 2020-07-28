<?php
namespace WishKnish\KnishIO\Client\Exception;


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
     * @param \Throwable|null $previous
     */
    public function __construct ( $message = 'Unauthenticated.', $code = 2, $previous = null )
    {
        parent::__construct( $message, $code, $previous );
    }
}
