<?php

namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class MolecularHashMissingException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class NegativeMeaningException extends BaseException
{
    /**
     * MolecularHashMissingException constructor.
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct ( $message = 'Negative meaning', $code = 1, $previous = null )
    {
        parent::__construct( $message, $code, $previous );
    }
}