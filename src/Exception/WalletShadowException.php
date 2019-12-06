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
	public function __construct ( $message = 'The shadow wallet does not exist', $code = 1, \Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
