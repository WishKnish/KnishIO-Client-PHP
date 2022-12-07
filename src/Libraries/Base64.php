<?php

namespace WishKnish\KnishIO\Client\Libraries;

use WishKnish\KnishIO\Client\Exception\CodeException;

class Base64 {

    public function encode ( $data ): string {
        return base64_encode( $data );
    }

    /**
     * @throws CodeException
     */
    public function decode ( $data ): string {
        $decode = base64_decode( $data, true );

        if ( $decode === false ) {
            throw new CodeException( 'The string does not match the base64 encoding.' );
        }

        return $decode;
    }
}
