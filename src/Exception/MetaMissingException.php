<?php
namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class MetaMissingException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class MetaMissingException extends BaseException
{

    /**
     * MetaMissingException constructor.
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct ( string $message = null, int $code = 0, \Throwable $previous = null )
    {
		$message	= default_if_null ($message, 'Empty meta data.');
		$code		= default_if_null ($code, 1);

        parent::__construct( $message, $code, $previous );

    }

}
