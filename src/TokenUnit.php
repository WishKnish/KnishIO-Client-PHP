<?php

namespace WishKnish\KnishIO\Client;


/**
 * Class TokenUnit
 * @package WishKnish\KnishIO\Client
 */
class TokenUnit {

  /**
   * TokenUnit constructor.
   *
   * @param string $id
   * @param string|null $name
   * @param array $metas
   */
  public function __construct(
    public string $id,
    public ?string $name = null,
    public array $metas = []
  ) {

  }

  /**
   * @return int|null
   */
  public function getFragmentZone(): ?int {
    return array_get( $this->metas, 'fragmentZone' );
  }

  /**
   * @return array
   */
  public function getData(): array {
    return [ $this->id, $this->name, $this->metas, ];
  }

  /**
   * @return string
   * @throws \JsonException
   */
  public function __toString (): string {
    return json_encode( $this->getData(), JSON_THROW_ON_ERROR );
  }

}
