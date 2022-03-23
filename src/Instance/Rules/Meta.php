<?php
namespace WishKnish\KnishIO\Client\Instance\Rules;

use WishKnish\KnishIO\Client\Traits\Json;


class Meta {
  use Json;

  public function __construct (
    public string $metaType,
    public string $metaId,
    public ?string $isotope = null,
    public ?string $token = null,
    public ?float $amount = null
  ) {
  }
}
