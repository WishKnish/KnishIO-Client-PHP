<?php

namespace WishKnish\KnishIO\Client;



/**
 * Class AuthToken
 */
class AuthToken {

  protected string $token;
  protected ?int $expiresAt;
  protected string $pubkey;
  protected bool $encrypt;

  protected ?Wallet $wallet;


  /**
   * @param $data
   * @param $wallet
   * @param $encrypt
   *
   * @return static
   */
  public static function create ( $data, $wallet, $encrypt ): self {
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
   * @param $snapshot
   * @param $secret
   *
   * @return static
   * @throws \Exception
   */
  public static function restore ( $snapshot, $secret ): self {
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
    $token,
    $expiresAt,
    $pubkey,
    $encrypt
  ) {
    $this->token = $token;
    $this->expiresAt = $expiresAt;
    $this->pubkey = $pubkey;
    $this->encrypt = $encrypt;
  }


  /**
   * @param $wallet
   */
  public function setWallet ( $wallet ): void {
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
   * @return float|int
   */
  public function getExpireInterval (): ?int {
    if ( !$this->expiresAt ) {
      return null;
    }
    return ( $this->expiresAt * 1000 ) - ( microtime() / 1000 );
  }


  /**
   * @return bool
   */
  public function isExpired (): bool {
    return !$this->expiresAt || $this->getExpireInterval() < 0;
  }


  /**
   * Get auth data for the final client (apollo)
   *
   * @return array
   */
  public function getAuthData (): array {
    return [
      'token' => $this->getToken(),
      'pubkey' => $this->getPubkey(),
      'wallet' => $this->getWallet(),
    ];
  }

}
