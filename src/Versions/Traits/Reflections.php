<?php

namespace WishKnish\KnishIO\Client\Versions\Traits;

use WishKnish\KnishIO\Client\Versions\HashAtom;

trait Reflections
{
  private static function reflections(): array {
    return [
      '4' => new class extends HashAtom {
        public function __construct(
          public ?string $position = null,
          public ?string $walletAddress = null,
          public ?string $isotope = null,
          public ?string $token = null,
          public ?string $value = null,
          public ?string $batchId = null,
          public ?string $metaType = null,
          public ?string $metaId = null,
          public ?array $meta = null,
          public ?int $index = null,
          public ?string $createdAt = null,
          public ?string $version = null
        ){}
      }
    ];
  }
}
