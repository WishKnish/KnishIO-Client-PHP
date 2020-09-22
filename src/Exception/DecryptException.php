<?php

namespace WishKnish\KnishIO\Client\Exception;


/**
 * Class DecryptException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class DecryptException extends BaseException
{
    /**
     * InvalidResponseException constructor.
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct ( $message = 'Error during decryption.', $code = 2, $previous = null )
    {
        parent::__construct( $message, $code, $previous );
    }
}
