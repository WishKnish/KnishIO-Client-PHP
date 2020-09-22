<?php

namespace WishKnish\KnishIO\Client\Exception;

use LogicException;

/**
 * Class BaseException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
abstract class BaseException extends LogicException implements IException
{
	protected $message = 'Unknown exception';
	protected $code = 0;
	protected $file;
	protected $line;

	/**
	 * BaseException constructor.
	 * @param null $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct ( $message = null, $code = 0, $previous = null )
	{
		if ( !$message ) {
			throw new static ( 'Unknown ' . static::class );
		}

		parent::__construct( $message, $code, $previous );
	}

	/**
	 * @return string
	 */
	public function __toString ()
	{
		return static::class . " '" . $this->message . "' in " . $this->file . ' (' . $this->line . ')' . PHP_EOL . $this->getTraceAsString();
	}
}
