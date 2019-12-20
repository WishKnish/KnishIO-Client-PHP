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
    public function __construct ( $message = 'Empty meta data.', $code = 1, \Throwable $previous = null )
    {
        parent::__construct( $message, $code, $previous );

    }

}
