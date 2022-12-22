<?php
/*
                               (
                              (/(
                              (//(
                              (///(
                             (/////(
                             (//////(                          )
                            (////////(                        (/)
                            (////////(                       (///)
                           (//////////(                      (////)
                           (//////////(                     (//////)
                          (////////////(                    (///////)
                         (/////////////(                   (/////////)
                        (//////////////(                  (///////////)
                        (///////////////(                (/////////////)
                       (////////////////(               (//////////////)
                      (((((((((((((((((((              (((((((((((((((
                     (((((((((((((((((((              ((((((((((((((
                     (((((((((((((((((((            ((((((((((((((
                    ((((((((((((((((((((           (((((((((((((
                    ((((((((((((((((((((          ((((((((((((
                    (((((((((((((((((((         ((((((((((((
                    (((((((((((((((((((        ((((((((((
                    ((((((((((((((((((/      (((((((((
                    ((((((((((((((((((     ((((((((
                    (((((((((((((((((    (((((((
                   ((((((((((((((((((  (((((
                   #################  ##
                   ################  #
                  ################# ##
                 %################  ###
                 ###############(   ####
                ###############      ####
               ###############       ######
              %#############(        (#######
             %#############           #########
            ############(              ##########
           ###########                  #############
          #########                      ##############
        %######

        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\HttpClient;

use ArrayObject;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Libraries\Cipher;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class HttpClient
 * @package WishKnish\KnishIO\HttpClient
 */
class HttpClient extends Client implements HttpClientInterface {

    /**
     * @var string|null
     */
    private ?string $authToken;

    /**
     * @var string
     */
    protected string $uri;

    /**
     * @var Cipher
     */
    private Cipher $cipher;

    /**
     * @var array
     */
    private array $config;

    /**
     * HttpClient constructor.
     *
     * @param string $uri
     * @param array $config
     * @param bool $encrypt
     */
    public function __construct ( string $uri, array $config = [], bool $encrypt = false ) {
        $this->setUri( $uri );
        $this->cipher = new Cipher();
        $this->authToken = null;
        $this->config = [
            'base_uri' => $uri,
            'handler' => $this->cipher->stack(),
            'encrypt' => $encrypt,
            RequestOptions::VERIFY => false,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::HEADERS => [
                'User-Agent' => 'KnishIO/0.1',
                'Accept' => 'application/json',
            ],
        ];

        // Merge config
        $config = array_replace_recursive( $this->config, $config );

        // Guzzle constructor
        parent::__construct( $config );
    }

    /**
     * @param bool $encrypt
     *
     * @return void
     */
    public function setEncryption ( bool $encrypt ): void {
        $this->config[ 'encrypt' ] = $encrypt;
    }

    /**
     * @return bool
     */
    public function hasEncryption (): bool {
        return $this->config[ 'encrypt' ];
    }

    /**
     * @param Wallet $wallet
     */
    public function setWallet ( Wallet $wallet ): void {
        $this->cipher->setWallet( $wallet );
    }

    /**
     * @return Wallet
     * @throws KnishIOException
     */
    public function wallet (): Wallet {
        return $this->cipher->wallet();
    }

    /**
     * @param string $pubkey
     */
    public function setPubkey ( ?string $pubkey ): void {
        $this->cipher->setPubkey( $pubkey );
    }

    /**
     * @return string
     * @throws KnishIOException
     */
    public function getPubkey (): string {
        return $this->cipher->getPubkey();
    }

    /**
     * @return string
     */
    public function getUri (): string {
        return $this->uri;
    }

    /**
     * @param string $uri
     */
    public function setUri ( string $uri ): void {
        $this->uri = $uri;
    }

    /**
     * @param string $authToken
     */
    public function setAuthToken ( string $authToken ): void {
        $this->authToken = $authToken;
    }

    /**
     * @return string|null
     */
    public function getAuthToken (): ?string {
        return $this->authToken;
    }

    /**
     * Sets the authorization data
     *
     * @param string $token
     * @param string|null $pubkey
     * @param Wallet $wallet
     */
    public function setAuthData ( string $token, ?string $pubkey, Wallet $wallet ): void {
        $this->setAuthToken( $token );
        $this->setPubkey( $pubkey );
        $this->setWallet( $wallet );
    }

    /**
     * @param RequestInterface $request
     * @param array $options
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function send ( RequestInterface $request, array $options = [] ): ResponseInterface {
        $config = array_replace_recursive( $this->config, $options );

        return parent::send( $request, $config );
    }

    /**
     * @param string $method
     * @param array $args
     *
     * @return Promise\PromiseInterface
     */
    public function __call ( $method, $args ) {

        $opts = ( new ArrayObject( $args ) )->getArrayCopy();
        $opts[ 1 ] = array_replace_recursive( $this->config, $opts[ 1 ] ?? [] );

        return parent::__call( $method, $opts );
    }

}
