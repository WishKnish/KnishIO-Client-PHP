<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

class IsotopeCountException extends KnishIOException {
    public function __construct ( string $message = 'An invalid number of atoms were provided for the given isotope.', $payload = null, $code = 0, Throwable $previous = null ) {
        parent::__construct( $message, $payload, $code, $previous );
    }
}
