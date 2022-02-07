<?php

namespace WishKnish\KnishIO\Client;

use Exception;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;

/**
 * Class AuthToken
 */
class AuthToken {

  protected string $token;
  protected ?string $expiresAt;
  protected string $pubkey;
  protected bool $encrypt;

  protected ?Wallet $wallet;

  /**
   * @param array $data
   * @param Wallet $wallet
   * @param bool $encrypt
   *
   * @return static
   */
  public static function create ( array $data, Wallet $wallet, bool $encrypt ): self {
    $authToken = new static (
      $data[ 'token' ],
      $data[ 'expiresAt' ],
      $data[ 'pubkey' ],
      $encrypt,
    );
    $authToken->setWallet( $wallet );
    return $authToken;
  }

  /**
   * @param array $snapshot
   * @param string $secret
   *
   * @return static
   * @throws Exception
   */
  public static function restore ( array $snapshot, string $secret ): self {
    $wallet = new Wallet (
      $secret,
      'AUTH',
      array_get( $snapshot, 'wallet.position' ),
      null,
      array_get( $snapshot, 'wallet.characters' )
    );
    return static::create( [
      'token' => array_get( $snapshot, 'token' ),
      'expiresAt' => array_get( $snapshot, 'expiresAt' ),
      'pubkey' => array_get( $snapshot, 'pubkey' ),
    ], $wallet, array_get( $snapshot, 'encrypt' ) );
  }

  /**
   * AuthToken constructor.
   *
   * @param $token
   * @param $expiresAt
   * @param $pubkey
   * @param $encrypt
   */
  public function __construct (
    string $token,
    mixed $expiresAt, // @todo string?
    string $pubkey,
    bool $encrypt
  ) {
    $this->token = $token;
    $this->expiresAt = $expiresAt;
    $this->pubkey = $pubkey;
    $this->encrypt = $encrypt;
  }

  /**
   * @param Wallet $wallet
   */
  public function setWallet ( Wallet $wallet ): void {
    $this->wallet = $wallet;
  }

  /**
   * @return mixed
   */
  public function getWallet (): Wallet {
    return $this->wallet;
  }

  /**
   * @return array
   */
  #[ArrayShape( [
    'token' => "string",
    'expiresAt' => "int|null",
    'pubkey' => "string",
    'encrypt' => "bool",
    'wallet' => "array"
  ] )]
  public function getSnapshot (): array {
    return [
      'token' => $this->token,
      'expiresAt' => $this->expiresAt,
      'pubkey' => $this->pubkey,
      'encrypt' => $this->encrypt,
      'wallet' => [
        'position' => $this->wallet->position,
        'characters' => $this->wallet->characters,
      ],
    ];
  }

  /**
   * @return mixed
   */
  public function getToken (): string {
    return $this->token;
  }

  /**
   * @return mixed
   */
  public function getPubkey (): string {
    return $this->pubkey;
  }

  /**
   * @return string
   */
  public function getExpireInterval (): string {
    if ( !$this->expiresAt ) {
      return -1;
    }
    return ( $this->expiresAt * 1000 ) - ( microtime() / 1000 );
  }

  /**
   * @return bool
   */
  #[Pure]
  public function isExpired (): bool {
    return !$this->expiresAt || $this->getExpireInterval() < 0;
  }

  /**
   * Get auth data for the final client (apollo)
   *
   * @return array
   */
  #[Pure]
  #[ArrayShape( [
    'token' => "mixed|string",
    'pubkey' => "mixed|string",
    'wallet' => "mixed|\WishKnish\KnishIO\Client\Wallet"
  ] )]
  public function getAuthData (): array {
    return [
      'token' => $this->getToken(),
      'pubkey' => $this->getPubkey(),
      'wallet' => $this->getWallet(),
    ];
  }

}
