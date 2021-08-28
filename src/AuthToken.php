<?php

namespace WishKnish\KnishIO\Client;


/**
 * Class AuthToken
 */
class AuthToken {

  protected ?Wallet $wallet;


  /**
   * @param $data
   * @param $wallet
   *
   * @return static
   */
  public static function create ( $data, $wallet ) {
    $authToken = new static (
        $data[ 'token' ],
        $data[ 'expiresAt' ],
        $data[ 'time' ],
        $data[ 'key' ],
        $data[ 'encrypt' ],
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
  public static function restore ( $snapshot, $secret ) {
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
      'time' => array_get( $snapshot, 'time' ),
      'pubkey' => array_get( $snapshot, 'pubkey' ),
      'encrypt' => array_get( $snapshot, 'encrypt' ),
    ], $wallet );
  }


  /**
   * AuthToken constructor.
   *
   * @param $token
   * @param $expiresAt
   * @param $time
   * @param $pubkey
   * @param $encrypt
   */
  public function __construct (
    $token,
    $expiresAt,
    $time,
    $pubkey,
    $encrypt
  ) {
    $this->token = $token;
    $this->expiresAt = $expiresAt;
    $this->time = $time;
    $this->pubkey = $pubkey;
    $this->encrypt = $encrypt;
  }


  /**
   * @param $wallet
   */
  public function setWallet ( $wallet ) {
    $this->wallet = $wallet;
  }


  /**
   * @return mixed
   */
  public function getWallet () {
    return $this->wallet;
  }


  /**
   * @return array
   */
  public function getSnapshot () {
    return [
      'token' => $this->token,
      'expiresAt' => $this->expiresAt,
      'time' => $this->time,
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
  public function getToken () {
    return $this->token;
  }


  /**
   * @return mixed
   */
  public function getPubkey () {
    return $this->pubkey;
  }


  /**
   * @return float|int
   */
  public function getExpireInterval () {
    return ( $this->expiresAt * 1000 ) - ( microtime() / 1000 );
  }


  /**
   * @return bool
   */
  public function isExpired () {
    return !$this->expiresAt || $this->getExpireInterval() < 0;
  }


  /**
   * Get auth data for the final client (apollo)
   *
   * @return array
   */
  public function getAuthData () {
    return [
      'token' => $this->getToken(),
      'pubkey' => $this->getPubkey(),
      'wallet' => $this->getWallet(),
    ];
  }

}
