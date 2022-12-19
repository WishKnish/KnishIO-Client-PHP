<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

class IsotopeIndexException extends KnishIOException {
    public function __construct ( string $message = 'The given Atom is in the wrong index.', $payload = null, int $code = 0, Throwable $previous = null ) {
        parent::__construct( $message, $payload, $code, $previous );
    }
}
