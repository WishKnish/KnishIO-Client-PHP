<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

class MoleculeExistsException extends KnishIOException {
    public function __construct ( string $message = 'Molecule already exists in ledger.', $payload = null, int $code = 0, Throwable $previous = null ) {
        parent::__construct( $message, $payload, $code, $previous );
    }
}
