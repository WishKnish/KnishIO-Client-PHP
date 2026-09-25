<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\Exception\TransferUnbalancedException;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Cross-SDK parity (JS CheckMolecule.isotopeV): a 2-atom V transaction must conserve value:
 * the two V atoms' values must sum to zero (e.g. source -1000 + recipient +1000 = 0).
 * An unbalanced 2-atom transfer (e.g. -1000 / +500) must be rejected with TransferUnbalancedException.
 */
class CheckMoleculeIsotopeVTest extends TestCase {

  private function signedTwoAtomTransfer ( string $debit, string $credit ): Molecule {
    $secret = Crypto::generateSecret( 'isotope-v-two-atom-test', 2048 );
    $source = new Wallet( $secret, 'TEST', '0123456789abcdeffedcba9876543210fedcba9876543210fedcba9876543210' );
    $recipient = new Wallet( Crypto::generateSecret( 'isotope-v-two-atom-recipient', 2048 ), 'TEST', 'fedcba9876543210fedcba9876543210fedcba9876543210fedcba9876543210' );
    $molecule = new Molecule( $secret, $source );
    $molecule->addAtom( Atom::create( 'V', $source, $debit ) );
    $molecule->addAtom( Atom::create( 'V', $recipient, $credit, 'walletBundle', $recipient->bundle ) );
    $molecule->sign();
    return $molecule;
  }

  public function testUnbalancedTwoAtomTransferIsRejected (): void {
    $this->expectException( TransferUnbalancedException::class );
    ( new CheckMolecule( $this->signedTwoAtomTransfer( '-1000', '500' ) ) )->isotopeVB();
  }

  public function testBalancedTwoAtomTransferPasses (): void {
    ( new CheckMolecule( $this->signedTwoAtomTransfer( '-1000', '1000' ) ) )->isotopeVB();
    $this->addToAssertionCount( 1 );
  }
}
