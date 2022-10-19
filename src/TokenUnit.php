<?php

namespace WishKnish\KnishIO\Client;

use JetBrains\PhpStorm\Pure;
use JsonException;
use WishKnish\KnishIO\Client\Exception\TokenUnitFormatException;

/**
 * Class TokenUnit
 * @package WishKnish\KnishIO\Client
 */
class TokenUnit {

  /**
   * @param Wallet $sourceWallet
   * @param array|null $sendTokenUnits
   *
   * @return array
   */
  #[Pure]
  public static function split ( Wallet $sourceWallet, ?array $sendTokenUnits ): array {

    // Not a token units transfer
    if ( $sendTokenUnits === null ) {
      return [
        0,
        [],
        []
      ];
    }

    // Calculate amount
    $amount = count( $sendTokenUnits );

    // Init recipient & remainder token units
    $recipientTokenUnits = [];
    $remainderTokenUnits = [];
    foreach ( $sourceWallet->tokenUnits as $tokenUnit ) {
      if ( in_array( $tokenUnit->id, $sendTokenUnits, true ) ) {
        $recipientTokenUnits[] = $tokenUnit;
      }
      else {
        $remainderTokenUnits[] = $tokenUnit;
      }
    }

    return [
      $amount,
      $recipientTokenUnits,
      $remainderTokenUnits,
    ];
  }

  /**
   * @param array $tokenUnits
   *
   * @return array
   */
  public static function getListGraphQLResponse ( array $tokenUnits ): array {
    $result = [];
    foreach ( $tokenUnits as $tokenUnit ) {
      $result[] = $tokenUnit->toGraphQLResponse();
    }
    return $result;
  }

  /**
   * @param mixed $id
   * @param mixed $name
   * @param mixed $metas
   *
   * @return static
   */
  public static function create ( mixed $id, mixed $name, mixed $metas ): self {
    if ( !is_string( $id ) || !$id ) {
      throw new TokenUnitFormatException( 'Invalid token unit ID format: non-empty string expected.' );
    }
    if ( !is_string( $name ) && !is_null( $name ) ) {
      throw new TokenUnitFormatException( 'Invalid token unit Name format: string OR null expected.' );
    }
    if ( !is_array( $metas ) ) {
      throw new TokenUnitFormatException( 'Invalid token unit Metas format: array expected.' );
    }
    return new self( $id, $name, $metas );
  }

  /**
   * @param array $data
   *
   * @return static
   */
  public static function createFromGraphQL ( array $data ): self {
    $metas = array_get( $data, 'metas', [] );
    if ( $metas ) {
      $metas = json_decode( $metas, true, JSON_THROW_ON_ERROR );
    }

    // Get token unit ID
    $tokenUnitId = array_get( $data, 'id' );
    if ( !is_string( $tokenUnitId ) || !$tokenUnitId ) {
      throw new TokenUnitFormatException();
    }

    // Create a new token unit
    return static::create( $tokenUnitId, array_get( $data, 'name' ), $metas );
  }

  /**
   * @param array|string $data
   *
   * @return static
   */
  public static function createFromDB ( array|string $data ): self {

    // !!! @todo supports wrong token creation with simple array: need to be deleted after db clearing
    if ( !is_array( $data ) ) {
      return new self( $data );
    }

    // Get token unit ID
    $tokenUnitId = array_get( $data, 0 );
    if ( !is_string( $tokenUnitId ) || !$tokenUnitId ) {
      throw new TokenUnitFormatException();
    }

    // Standard token unit format
    return static::create( $tokenUnitId, array_get( $data, 1 ), array_get( $data, 2, [] ) );
  }

  /**
   * TokenUnit constructor.
   *
   * @param string $id
   * @param string|null $name
   * @param array $metas
   */
  public function __construct ( public string $id, public ?string $name = null, public array $metas = [] ) {

  }

  /**
   * @return int|null
   */
  public function getFragmentZone (): ?int {
    return array_get( $this->metas, 'fragmentZone' );
  }

  /**
   * @return array
   */
  public function getFusedTokenUnits (): array {
    return array_get( $this->metas, 'fusedTokenUnits', [] );
  }

  /**
   * @return array
   */
  public function toData (): array {
    return [
      $this->id,
      $this->name,
      $this->metas,
    ];
  }

  /**
   * @return array
   * @throws JsonException
   */
  public function toGraphQLResponse (): array {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'metas' => json_encode( $this->metas, JSON_THROW_ON_ERROR ),
    ];
  }

  /**
   * @return string
   * @throws JsonException
   */
  public function __toString (): string {
    return json_encode( $this->toData(), JSON_THROW_ON_ERROR );
  }

}
