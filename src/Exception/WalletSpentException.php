<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

class WalletSpentException extends KnishIOException {
    public function __construct ( string $message = 'This Wallets has already been used to sign a Molecule.', $payload = null, int $code = 0, Throwable $previous = null ) {
        parent::__construct( $message, $payload, $code, $previous );
    }
}
