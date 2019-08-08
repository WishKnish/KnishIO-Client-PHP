<?php
namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class InvalidResponseException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class InvalidResponseException extends BaseException
{
    /**
     * InvalidResponseException constructor.
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct ( $message = 'GraphQL did not provide a valid response.', $code = 2, \Throwable $previous = null )
    {
        parent::__construct( $message, $code, $previous );
    }
}
