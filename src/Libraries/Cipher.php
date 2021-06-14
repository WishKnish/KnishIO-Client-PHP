<?php
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
use function GuzzleHttp\json_encode;



class Cipher {

  /**
   * @var Wallet|null
   */
  private ?Wallet $wallet;

  /**
   * @var string|null
   */
  private ?string $pubKey;

  /**
   * Cipher constructor.
   *
   * @param ?Wallet $wallet
   * @param ?string $pubKey
   */
  public function __construct ( ?Wallet $wallet = null, ?string $pubKey = null ) {
    $this->wallet = $wallet;
    $this->pubKey = $pubKey;
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
   * @param string $pubKey
   */
  public function setPubKey ( string $pubKey ): void {
    $this->pubKey = $pubKey;
  }

  /**
   * @return string
   */
  public function pubKey (): string {
    if ( $this->pubKey === null ) {
      throw new CodeException( 'Server public key missing.' );
    }

    return $this->pubKey;
  }

  /**
   * @return Closure
   */
  protected function handler (): Closure {

    return function ( callable $handler ) {

      return function ( RequestInterface $request, array $options ) use ( $handler ) {

        if ( array_get( $options, 'encrypt', false ) ) {

          $requestContent = $request->getBody()->getContents();
          $original = json_decode( $requestContent, true, 512, JSON_THROW_ON_ERROR );

          if ( array_key_exists( 'query', $original ) ) {

            $requestName = static::graphqlParse( $original['query'], 'name' );
            $requestType = static::graphqlParse( $original['query'] );
            $isMoleculeMutation = ( $requestType === 'mutation' && $requestName === 'ProposeMolecule' );
            $conditions = [
              ( $requestType === 'query' && in_array( $requestName, [ '__schema', 'ContinuId' ] ) ),
              ( $requestType === 'mutation' && $requestName === 'AccessToken' ),
              ( $isMoleculeMutation && array_get( $original, 'variables.molecule.atoms.0.isotope' ) === 'U' )
            ];

            if ( in_array( true, $conditions, true ) ) {
              return $handler( $request, $options );
            }

            $content = [
              'query' => 'query ( $Hash: String! ) { CipherHash ( Hash: $Hash ) { hash } }',
              'variables' => [
                'Hash' => json_encode( $this->wallet()->encryptMyMessage( $original, $this->pubKey() ), JSON_THROW_ON_ERROR )
              ]
            ];

            $promise = $handler(
              $request->withBody( Utils::streamFor( json_encode( $content ) ) ),
              $options
            );

            return $promise->then( $this->response( $options ) );
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
  protected function response( array $options ): Closure {

    return function ( ResponseInterface $response ) use ( $options ) {

      if ( array_get( $options, 'encrypt', false ) ) {

        $original = json_decode(
          $response->getBody()->getContents(),
          true,
          512,
          JSON_THROW_ON_ERROR
        );

        $data = array_has( $original,'data.data') ?
          array_get( $original, 'data.data' ) :
          array_get( $original, 'data' );

        if ( $data ) {
          if ( array_has( $data, 'CipherHash' ) ) {

            $encrypted = array_get( $data, 'CipherHash.hash' );

            if ( $encrypted ) {

              $decryption = $this->wallet()
                ->decryptMyMessage( json_decode( $encrypted, true, 512, JSON_THROW_ON_ERROR ) );

              if ( $decryption === null ) {
                throw new InvalidResponseException( 'Error decoding response.' );
              }

              return $response->withBody( Utils::streamFor( json_encode( $decryption ) ) );
            }
          }

          throw new InvalidResponseException( 'Incorrect response format.' );
        }

        return $response->withBody( Utils::streamFor( json_encode( $original ) ) );
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
        if ( in_array( $item->operation, [
          'query',
          'mutation',
          'subscription'
        ] ) ) {
          return $operation === 'type' ? $item->operation : $node->name->value;
        }
      }
    }

    return null;
  }

}