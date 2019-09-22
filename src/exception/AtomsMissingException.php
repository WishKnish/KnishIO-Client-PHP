<?php

namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class AtomsMissingException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class AtomsMissingException extends BaseException
{
	/**
	 * AtomsMissingException constructor.
	 * @param string $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct ( $message = 'The molecule does not contain atoms', $code = 1, \Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
