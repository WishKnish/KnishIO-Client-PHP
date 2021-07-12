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
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise;
use WishKnish\KnishIO\Client\Libraries\Cipher;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class HttpClient
 * @package WishKnish\KnishIO\HttpClient
 */
class HttpClient extends \GuzzleHttp\Client implements HttpClientInterface {

  /**
   * @var string|null
   */
  private ?string $xAuthToken;

  /**
   * @var string
   */
  protected string $url;

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
   * @param string $url
   * @param array $config
   * @param bool $encrypt
   */
  public function __construct ( string $url, array $config = [], bool $encrypt = false ) {
    $this->setUrl( $url );
    $this->cipher = new Cipher();
    $this->xAuthToken = null;
    $this->config = [ 'base_uri' => $url, 'handler' => $this->cipher->stack(), 'encrypt' => $encrypt, RequestOptions::VERIFY => false, RequestOptions::HTTP_ERRORS => false, RequestOptions::HEADERS => [ 'User-Agent' => 'KnishIO/0.1', 'Accept' => 'application/json', ], ];

    // Merge config
    $config = array_replace_recursive( $this->config, $config );

    // Guzzle constructor
    parent::__construct( $config );
  }

  public function enableEncryption (): void {
    $this->config[ 'encrypt' ] = true;
  }

  public function disableEncryption (): void {
    $this->config[ 'encrypt' ] = false;
  }

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
   */
  public function wallet (): Wallet {
    return $this->cipher->wallet();
  }

  /**
   * @param string $pubKey
   */
  public function setPubKey ( string $pubKey ): void {
    $this->cipher->setPubKey( $pubKey );
  }

  /**
   * @return string
   */
  public function pubKey (): string {
    return $this->cipher->pubKey();
  }

  /**
   * @return string
   */
  public function getUrl (): string {
    return $this->url;
  }

  /**
   * @param string $url
   *
   * @return void
   */
  public function setUrl ( string $url ): void {
    $this->url = $url;
  }

  /**
   * @param string $authToken
   */
  public function setAuthToken ( string $authToken ): void {
    $this->xAuthToken = $authToken;
  }

  /**
   * @return string|null
   */
  public function getAuthToken (): ?string {
    return $this->xAuthToken;
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
