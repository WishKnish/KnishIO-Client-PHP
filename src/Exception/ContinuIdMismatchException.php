<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

class ContinuIdMismatchException extends KnishIOException {
    public function __construct ( string $message = 'ContinuID failure: signing Wallets must be designated by previous Molecule.', $payload = null, int $code = 0, Throwable $previous = null ) {
        parent::__construct( $message, $payload, $code, $previous );
    }
}
