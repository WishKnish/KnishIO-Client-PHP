<?php

namespace WishKnish\KnishIO\Client;


/**
 * Class TokenUnit
 * @package WishKnish\KnishIO\Client
 */
class TokenUnit {

  /**
   * @param array $data
   *
   * @return static
   */
  public static function createFromGraphQL( array $data ): self {
    $metas = array_get( $data, 'metas', [] );
    if ( $metas ) {
      $metas = json_decode( $metas, true, JSON_THROW_ON_ERROR );
    }
    return new self(
      array_get( $data, 'id' ),
      array_get( $data, 'name' ),
      $metas,
    );
  }

  /**
   * @param array|string $data
   *
   * @return static
   */
  public static function createFromDB( array|string $data ): self {

    // !!! @todo supporting wrong token creation with simple array: need to be deleted after db clearing
    if ( !is_array( $data ) ) {
      return new self( $data );
    }

    // Standard token unit format
    return new self(
      array_get( $data, 0 ),
      array_get( $data, 1 ),
      array_get( $data, 2, [] )
    );
  }

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
  public function toRawData(): array {
    return [ $this->id, $this->name, $this->metas, ];
  }

  /**
   * @return array
   * @throws \JsonException
   */
  public function toGraphQLResponse(): array {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'metas' => json_encode( $this->metas, JSON_THROW_ON_ERROR ),
    ];
  }

  /**
   * @return string
   * @throws \JsonException
   */
  public function __toString (): string {
    return json_encode( $this->toRawData(), JSON_THROW_ON_ERROR );
  }

}
