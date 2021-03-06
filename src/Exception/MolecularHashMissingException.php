<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

/**
 * Class MolecularHashMissingException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class MolecularHashMissingException extends BaseException
{
	/**
	 * MolecularHashMissingException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct ( $message = 'The molecular hash is missing', $code = 1, Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
