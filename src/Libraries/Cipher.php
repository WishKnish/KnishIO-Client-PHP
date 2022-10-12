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

namespace WishKnish\KnishIO\Client\Libraries;

use Closure;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\Parser;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WishKnish\KnishIO\Client\Exception\CodeException;
use WishKnish\KnishIO\Client\Exception\InvalidRequestException;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Wallet;

class Cipher {

  /**
   * @var Wallet|null
   */
  private ?Wallet $wallet;

  /**
   * @var string|null
   */
  private ?string $pubkey;

  /**
   * Cipher constructor.
   *
   * @param ?Wallet $wallet
   * @param ?string $pubkey
   */
  public function __construct ( ?Wallet $wallet = null, ?string $pubkey = null ) {
    $this->wallet = $wallet;
    $this->pubkey = $pubkey;
  }

  /**
   * @param Wallet|null $wallet
   */
  public function setWallet ( ?Wallet $wallet ): void {
    $this->wallet = $wallet;
  }

  /**
   * @return Wallet
   */
  public function wallet (): Wallet {
    if ( $this->wallet === null ) {
      throw new CodeException( 'Authorized wallet missing.' );
    }
    return $this->wallet;
  }

  /**
   * @param string $pubkey
   */
  public function setPubkey ( string $pubkey ): void {
    $this->pubkey = $pubkey;
  }

  /**
   * @return string
   */
  public function getPubkey (): string {
    if ( $this->pubkey === null ) {
      throw new CodeException( 'Server public key missing.' );
    }

    return $this->pubkey;
  }

  /**
   * @return Closure
   */
  protected function handler (): Closure {

    return function ( callable $handler ) {

      return function ( RequestInterface $request, array $options ) use ( $handler ) {

        if ( array_get( $options, 'encrypt', false ) ) {

          $requestContent = $request->getBody()
            ->getContents();
          $original = json_decode( $requestContent, true, 512, JSON_THROW_ON_ERROR );

          if ( array_key_exists( 'query', $original ) ) {

            $requestName = static::graphqlParse( $original[ 'query' ], 'name' );
            $requestType = static::graphqlParse( $original[ 'query' ] );
            $isMoleculeMutation = ( $requestType === 'mutation' && $requestName === 'ProposeMolecule' );
            $conditions = [ ( $requestType === 'query' && in_array( $requestName, [ '__schema', 'ContinuId' ] ) ), ( $requestType === 'mutation' && $requestName === 'AccessToken' ), ( $isMoleculeMutation && array_get( $original, 'variables.molecule.atoms.0.isotope' ) === 'U' ) ];

            if ( in_array( true, $conditions, true ) ) {
              return $handler( $request, $options );
            }

            // Wallet::encryptMessage() result => [ hash1 => encrypted_message1, hash2 => encrypted_message2, ... ]
            $encryptedMessage = $this->wallet()
              ->encryptMessage( $original, $this->getPubkey() );

            // Full request context
            $content = [
              'query' => 'query ( $Hash: String! ) { CipherHash ( Hash: $Hash ) { hash } }', 'variables' => [
                'Hash' => json_encode( $encryptedMessage, JSON_THROW_ON_ERROR ),
              ],
            ];

            // Prepare content for sending
            $content = Utils::streamFor( json_encode( $content, JSON_THROW_ON_ERROR ) );

            // Send a request
            return $handler( $request->withBody( $content ), $options )->then( $this->response( $options ) );
          }

          throw new InvalidRequestException();
        }

        return $handler( $request, $options );
      };
    };
  }

  /**
   * @param array $options
   *
   * @return Closure
   */
  protected function response ( array $options ): Closure {

    return function ( ResponseInterface $response ) use ( $options ) {

      if ( array_get( $options, 'encrypt', false ) ) {

        $original = json_decode( $response->getBody()
          ->getContents(), true, 512, JSON_THROW_ON_ERROR );

        $data = array_has( $original, 'data.data' ) ? array_get( $original, 'data.data' ) : array_get( $original, 'data' );

        if ( $data ) {
          if ( array_has( $data, 'CipherHash' ) ) {

            $encrypted = array_get( $data, 'CipherHash.hash' );

            if ( $encrypted ) {

              $decryption = $this->wallet()
                ->decryptMessage( json_decode( $encrypted, true, 512, JSON_THROW_ON_ERROR ) );

              if ( $decryption === null ) {
                throw new InvalidResponseException( 'Error decoding response.' );
              }

              return $response->withBody( Utils::streamFor( json_encode( $decryption ) ) );
            }
          }

          throw new InvalidResponseException( 'Incorrect response format.' );
        }

        return $response->withBody( Utils::streamFor( json_encode( $original, JSON_THROW_ON_ERROR ) ) );
      }

      return $response;
    };
  }

  /**
   * @return HandlerStack
   */
  public function stack (): HandlerStack {

    $stack = new HandlerStack();
    $stack->setHandler( new CurlHandler() );
    $stack->push( $this->handler() );

    return $stack;
  }

  /**
   * @throws SyntaxError
   */
  public static function graphqlParse ( $content, string $type = 'type' ): ?string {

    $operation = strtolower( $type );

    if ( in_array( $operation, [ 'type', 'name' ], true ) ) {

      /** @var DocumentNode $type */
      $documents = Parser::parse( $content );

      if ( $documents->definitions->count() > 0 ) {

        /** @var OperationDefinitionNode $node */
        $item = $documents->definitions[ 0 ];

        /** @var FieldNode $node */
        $node = $item->selectionSet->selections[ 0 ];

        // Type & name initialization
        if ( in_array( $item->operation, [ 'query', 'mutation', 'subscription' ] ) ) {
          return $operation === 'type' ? $item->operation : $node->name->value;
        }
      }
    }

    return null;
  }

}
