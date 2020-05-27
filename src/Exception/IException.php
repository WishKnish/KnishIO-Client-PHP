<?php

namespace WishKnish\KnishIO\Client\Exception;


/**
 * Interface IException
 * @package WishKnish\KnishIO\Client\Exception
 */
interface IException
{
	public function getMessage ();

	public function getCode ();

	public function getFile ();

	public function getLine ();

	public function getTrace ();

	public function getTraceAsString ();

	public function __toString ();

	public function __construct ( $message = null, $code = 0, $previous = null );
}
