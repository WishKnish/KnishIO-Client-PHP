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
	public function __construct ( $message = 'The molecular hash does not match', $code = 1, \Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
