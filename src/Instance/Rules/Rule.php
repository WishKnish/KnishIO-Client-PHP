<?php
namespace WishKnish\KnishIO\Client\Instance\Rules;

use WishKnish\KnishIO\Client\Traits\Json;


class Rule {
  use Json;

  /**
   * @param string $key
   * @param string $value
   * @param string $comparison
   * @param array $callback
   */
  public function __construct (
    public string $key = '',
    public string $value = '',
    public string $comparison = '===',
    public array $callback = []
  ) {
  }
}
