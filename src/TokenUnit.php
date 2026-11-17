<?php

namespace WishKnish\KnishIO\Client;

use JetBrains\PhpStorm\Pure;
use JsonException;
use WishKnish\KnishIO\Client\Libraries\JsJson;
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
   * @throws JsonException
   */
  public static function createFromGraphQL ( array $data ): self {
    // The validator serves `metas` as a JSON string (or null); parse it as JS does: objects keep their
    // keys (PHP arrays would turn {} and {"0":…} into lists), a list stays a list, anything falsy is {}
    $metas = array_get( $data, 'metas' );
    $metasIsList = false;
    if ( is_string( $metas ) && $metas !== '' ) {
      $metas = json_decode( $metas, false, 512, JSON_THROW_ON_ERROR );
      $metasIsList = is_array( $metas );
    }
    if ( $metas instanceof \stdClass ) {
      $metas = get_object_vars( $metas );
    }
    elseif ( !is_array( $metas ) ) {
      $metas = [];
    }

    // Get token unit ID
    $tokenUnitId = array_get( $data, 'id' );
    if ( !is_string( $tokenUnitId ) || !$tokenUnitId ) {
      throw new TokenUnitFormatException();
    }

    // Create a new token unit
    $tokenUnit = static::create( $tokenUnitId, array_get( $data, 'name' ), $metas );
    $tokenUnit->metasIsList = $metasIsList;
    return $tokenUnit;
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

    // Standard token unit format; toData() writes metas as an object (stdClass), which reads back as its entries
    $metas = array_get( $data, 2, [] );
    return static::create( $tokenUnitId, array_get( $data, 1 ), $metas instanceof \stdClass ? get_object_vars( $metas ) : $metas );
  }

  /** Served metas that were a JSON list, which JS signs as a list */
  private bool $metasIsList = false;

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
   * The unit's [id, name, metas] triple as JS `JSON.stringify` writes it: metas are an object (`{}`
   * when empty) with integer-like keys first in ascending order at every depth, unless they were
   * served as a list.
   *
   * @return array
   */
  public function toData (): array {
    return [
      $this->id,
      $this->name,
      $this->metasIsList ? JsJson::value( $this->metas ) : JsJson::object( array_map( JsJson::value( ... ), $this->metas ) ),
    ];
  }

  /**
   * Compact JSON of a unit list as JS `JSON.stringify` writes it (no escaped slashes or unicode):
   * the value of a `tokenUnits` atom meta.
   *
   * @param TokenUnit[] $tokenUnits
   *
   * @return string
   * @throws JsonException
   */
  public static function encodeList ( array $tokenUnits ): string {
    return json_encode(
      array_map( static fn( TokenUnit $tokenUnit ) => $tokenUnit->toData(), array_values( $tokenUnits ) ),
      JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
  }

  /**
   * @return array
   * @throws JsonException
   */
  public function toGraphQLResponse (): array {
    // JS: metas is JSON.stringify(this.metas), so the object keeps JS key order and stays an object
    return [
      'id' => $this->id,
      'name' => $this->name,
      'metas' => json_encode( $this->toData()[ 2 ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ),
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
