<?php

namespace WishKnish\KnishIO\Client\Libraries;

use ArrayObject;
use Tuupola\Base58 as B58;

class BaseX {
  const BASE2  = '01';
  const BASE8  = '01234567';
  const BASE11 = '0123456789a';
  const BASE36 = '0123456789abcdefghijklmnopqrstuvwxyz';
  const BASE62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const BASE67 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.!~';

  private $encoder;

  public function __construct ( array $options = [] ) {
    $basex = [ 'BASE2', 'BASE8', 'BASE11', 'BASE36', 'BASE62', 'BASE67' ];
    $base58 = [ 'BITCOIN', 'FLICKR', 'RIPPLE', 'IPFS' ];
    $base64 = [ 'BASE64' ];
    $config = [
        "characters" => B58::GMP,
        "check" => false,
        "version" => 0x00,
    ];

    $this->encoder = new Base58( $config );

    $cloneOptions = ( new ArrayObject( $options ) )->getArrayCopy();
    $characters = array_get( $cloneOptions, 'characters' ) ?? 'BASE64';

    unset( $cloneOptions['characters'] );

    $config = array_merge( $config, $cloneOptions );

    if ( in_array( $characters, $base64, true ) ) {
      $this->encoder = new Base64();
    }
    else if ( in_array( $characters, $base58, true ) ) {
      $config[ 'characters' ] = constant( Base58::class . '::' . $characters );
      $this->encoder = new Base58( $config );
    }
    else if ( in_array( $characters, $basex, true ) ) {
      $config[ 'characters' ] = constant( static::class . '::' . $characters );
      $this->encoder = new Base58( $config );
    }
  }

  public function encode ( $data ): string {
    return $this->encoder->encode( $data );
  }

  public function decode ( $data ): string {
    return $this->encoder->decode( $data );
  }
}
