<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

/**
 * Class TransferUnbalancedException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class TransferUnbalancedException extends BaseException
{
	/**
	 * TransferUnbalancedException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct ( $message = 'Token transfer atoms are unbalanced', $code = 1, Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
