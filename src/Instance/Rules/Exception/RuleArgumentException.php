<?php
namespace WishKnish\KnishIO\Client\Instance\Rules\Exception;

use Throwable;
use WishKnish\KnishIO\Client\Exception\BaseException;

class RuleArgumentException extends BaseException {
  /**
   * RuleArgumentException constructor.
   *
   * @param string $message
   * @param int $code
   * @param Throwable|null $previous
   */
  public function __construct ( string $message = 'An incorrect argument!', int $code = 2, Throwable $previous = null ) {
    parent::__construct( $message, $code, $previous );
  }
}