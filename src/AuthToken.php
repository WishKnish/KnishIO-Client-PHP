<?php

namespace WishKnish\KnishIO\Client;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use SodiumException;

/**
 * Class AuthToken
 */
class AuthToken {

    /**
     * @var Wallet|null
     */
    protected ?Wallet $wallet;

    /**
     * @param array $data
     * @param Wallet $wallet
     * @param bool $encrypt
     *
     * @return static
     */
    public static function create ( array $data, Wallet $wallet, bool $encrypt ): self {
        $authToken = new static ( $data[ 'token' ], $data[ 'expiresAt' ], $data[ 'pubkey' ], $encrypt, );
        $authToken->setWallet( $wallet );
        return $authToken;
    }

    /**
     * @param array $snapshot
     * @param string $secret
     *
     * @return static
     * @throws SodiumException
     */
    public static function restore ( array $snapshot, string $secret ): self {
        $wallet = new Wallet ( $secret, 'AUTH', array_get( $snapshot, 'wallet.position' ), null, array_get( $snapshot, 'wallet.characters' ) );
        return static::create( [
            'token' => array_get( $snapshot, 'token' ),
            'expiresAt' => array_get( $snapshot, 'expiresAt' ),
            'pubkey' => array_get( $snapshot, 'pubkey' ),
        ], $wallet, array_get( $snapshot, 'encrypt' ) );
    }

    /**
     * @param string $token
     * @param string $expiresAt
     * @param string $pubkey
     * @param bool $encrypt
     */
    public function __construct (
        protected string $token,
        protected string $expiresAt,
        protected string $pubkey,
        protected bool $encrypt,
    ) {

    }

    /**
     * @param Wallet $wallet
     */
    public function setWallet ( Wallet $wallet ): void {
        $this->wallet = $wallet;
    }

    /**
     * @return Wallet
     */
    public function getWallet (): Wallet {
        return $this->wallet;
    }

    /**
     * @return array
     */
    #[ArrayShape( [
        'token' => "string",
        'expiresAt' => "string",
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
     * @return string
     */
    public function getToken (): string {
        return $this->token;
    }

    /**
     * @return string
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
        'token' => "string",
        'pubkey' => "string",
        'wallet' => Wallet::class
    ] )]
    public function getAuthData (): array {
        return [
            'token' => $this->getToken(),
            'pubkey' => $this->getPubkey(),
            'wallet' => $this->getWallet(),
        ];
    }

}
