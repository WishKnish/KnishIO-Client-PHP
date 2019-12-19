<?php

namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class WalletShadowException
 * @package WishKnish\KnishIO\Client\Exception
 */
class WalletShadowException extends BaseException
{
	/**
	 * TransferMismatchedException constructor.
	 * @param string $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct ( string $message = null, int $code = null, \Throwable $previous = null )
	{
		$message	= default_if_null ($message, 'The shadow wallet does not exist');
		$code		= default_if_null ($code, 1);

		parent::__construct( $message, $code, $previous );
	}
}
