<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

/**
 * Class AtomIndexException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class AtomIndexException extends BaseException
{
    /**
     * AtomIndexException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct ( $message = 'There is an atom without an index', $code = 1, Throwable $previous = null )
    {
        parent::__construct( $message, $code, $previous );
    }
}
