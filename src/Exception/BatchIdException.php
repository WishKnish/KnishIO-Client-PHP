<?php
namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class BatchIdException
 * @package WishKnish\KnishIO\Client\Exception
 */
class BatchIdException extends BaseException {
  /**
   * BatchIdException constructor.
   * @param string $message
   * @param int $code
   * @param \Throwable|null $previous
   */
  public function __construct ( $message = 'Incorrect BatchId', $code = 1, $previous = null )
  {
    parent::__construct( $message, $code, $previous );
  }
}
