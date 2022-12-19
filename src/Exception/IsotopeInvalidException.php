<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

class IsotopeInvalidException extends KnishIOException {
    public function __construct ( string $message = 'An invalid Atom isotope was encountered', $payload = null, int $code = 0, Throwable $previous = null ) {
        parent::__construct( $message, $payload, $code, $previous );
    }
}
