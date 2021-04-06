<?php
namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class StackableUnitDecimalsException
 * @package WishKnish\KnishIO\Client\Exception
 */
class StackableUnitDecimalsException extends BaseException {
  /**
   * StackableUnitDecimalsException constructor.
   * @param string $message
   * @param int $code
   * @param \Throwable|null $previous
   */
  public function __construct ( $message = 'Stackable tokens with unit IDs cannot have decimal places!', $code = 1, $previous = null )
  {
    parent::__construct( $message, $code, $previous );
  }
}