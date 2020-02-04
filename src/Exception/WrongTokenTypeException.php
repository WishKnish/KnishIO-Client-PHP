<?php
namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class WrongTokenTypeException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class WrongTokenTypeException extends BaseException
{
    /**
     * WrongTokenTypeException constructor.
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct ( $message = 'Wrong type of token for this isotope', $code = 1, \Throwable $previous = null )
    {
        parent::__construct( $message, $code, $previous );
    }
}
