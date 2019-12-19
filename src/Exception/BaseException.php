<?php

namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class BaseException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
abstract class BaseException extends \LogicException implements IException
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
	public function __construct ( string $message = null, int $code = null, \Throwable $previous = null )
	{
		$code = default_if_null ($code, 0);

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
