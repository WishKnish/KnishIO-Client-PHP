<?php

namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class MolecularHashMismatchException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class MolecularHashMismatchException extends BaseException
{
	/**
	 * MolecularHashMismatchException constructor.
	 * @param string $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct ( string $message = null, int $code = null, \Throwable $previous = null )
	{
		$message	= default_if_null ($message, 'The molecular hash does not match');
		$code		= default_if_null ($code, 1);

		parent::__construct( $message, $code, $previous );
	}
}
