<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

class ContinuIdTokenException extends KnishIOException {
    public function __construct ( string $message = 'Invalid token slug provided for ContinuID (USER required)', $payload = null, int $code = 0, Throwable $previous = null ) {
        parent::__construct( $message, $payload, $code, $previous );
    }
}
