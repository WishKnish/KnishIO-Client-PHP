<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\AtomMeta;
use WishKnish\KnishIO\Client\Exception\MetaMissingException;
use WishKnish\KnishIO\Client\Exception\PolicyInvalidException;
use WishKnish\KnishIO\Client\Exception\RuleArgumentException;
use WishKnish\KnishIO\Client\Exception\TokenTypeException;
use WishKnish\KnishIO\Client\Exception\TransferMalformedException;
use WishKnish\KnishIO\Client\Exception\TransferUnbalancedException;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Rule;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Molecule verification as JS CheckMolecule does it: V values parsed as numbers (not truncated to
 * integers), the P, A and M-policy rules, the JS Rule array form of R atoms, and fusion molecules
 * with two V atoms.
 */
class CheckMoleculeJsParityTest extends TestCase {

  private static function secret (): string {
    return Crypto::generateSecret( 'check-molecule-js-parity', 2048 );
  }

  private function signedTwoAtomTransfer ( string $debit, string $credit ): Molecule {
    $secret = self::secret();
    $source = new Wallet( $secret, 'TEST', str_repeat( 'a1', 32 ) );
    $recipient = new Wallet( Crypto::generateSecret( 'check-molecule-js-parity-recipient', 2048 ), 'TEST', str_repeat( 'b2', 32 ) );
    $molecule = new Molecule( $secret, $source );
    $molecule->addAtom( Atom::create( 'V', $source, $debit ) );
    $molecule->addAtom( Atom::create( 'V', $recipient, $credit, 'walletBundle', $recipient->bundle ) );
    $molecule->sign();
    return $molecule;
  }

  /**
   * A signed molecule whose first atom is built by $first from the USER source wallet, followed by a
   * ContinuID atom.
   */
  private function signedUserMolecule ( callable $first, string $token = 'USER' ): Molecule {
    $secret = self::secret();
    $source = new Wallet( $secret, $token, str_repeat( 'c3', 32 ) );
    $molecule = new Molecule( $secret, $source );
    $molecule->addAtom( $first( $source ) );
    $molecule->addContinuIdAtom();
    $molecule->sign();
    return $molecule;
  }

  // V values are numbers, not integers

  public function testFractionalUnbalancedTwoAtomTransferIsRejected (): void {
    $this->expectException( TransferUnbalancedException::class );
    ( new CheckMolecule( $this->signedTwoAtomTransfer( '-0.5', '0.9' ) ) )->isotopeVB();
  }

  public function testFractionalBalancedTwoAtomTransferPasses (): void {
    ( new CheckMolecule( $this->signedTwoAtomTransfer( '-0.5', '0.5' ) ) )->isotopeVB();
    $this->addToAssertionCount( 1 );
  }

  public function testNonNumericValueIsMalformed (): void {
    $this->expectException( TransferMalformedException::class );
    ( new CheckMolecule( $this->signedTwoAtomTransfer( '-1000', 'abc' ) ) )->isotopeVB();
  }

  // 2 V + 1 F fusion goes through the multi-atom rules, not the 2-atom transfer rule

  public function testTwoVPlusFusionVerifies (): void {
    $secret = self::secret();
    $source = new Wallet( $secret, 'TEST', str_repeat( 'd4', 32 ) );
    $source->balance = 2;
    $burn = new Wallet( Crypto::generateSecret( 'check-molecule-js-parity-burn', 2048 ), 'TEST', str_repeat( 'e5', 32 ) );
    $recipient = new Wallet( Crypto::generateSecret( 'check-molecule-js-parity-fused', 2048 ), 'TEST', str_repeat( 'f6', 32 ) );
    $molecule = new Molecule( $secret, $source );
    $molecule->addAtom( Atom::create( 'V', $source, '-2' ) );
    $molecule->addAtom( Atom::create( 'V', $burn, '1', 'walletBundle', $burn->bundle ) );
    $molecule->addAtom( Atom::create( 'F', $recipient, '1', 'walletBundle', $recipient->bundle ) );
    $molecule->sign();
    $molecule->check( $source );
    $this->addToAssertionCount( 1 );
  }

  // Isotope P

  public function testPeeringAtomWithoutPeerHostIsRejected (): void {
    $molecule = $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'P', $w, null, 'walletBundle', $w->bundle, new AtomMeta( [ 'name' => 'x' ] ) ) );
    $this->expectException( MetaMissingException::class );
    $this->expectExceptionMessage( 'isotopeP' );
    $molecule->check();
  }

  public function testPeeringAtomWithWrongTokenIsRejected (): void {
    $molecule = $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'P', $w, null, 'walletBundle', $w->bundle, new AtomMeta( [ 'peerHost' => 'node.example' ] ) ), 'TEST' );
    $this->expectException( TokenTypeException::class );
    $this->expectExceptionMessage( 'isotopeP' );
    $molecule->check();
  }

  public function testPeeringAtomVerifies (): void {
    $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'P', $w, null, 'walletBundle', $w->bundle, new AtomMeta( [ 'peerHost' => 'node.example' ] ) ) )->check();
    $this->addToAssertionCount( 1 );
  }

  // Isotope A

  public function testAppendAtomWithoutMetaTypeIsRejected (): void {
    $molecule = $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'A', $w, null, null, null, new AtomMeta( [ 'action' => 'append' ] ) ) );
    $this->expectException( MetaMissingException::class );
    $this->expectExceptionMessage( 'isotopeA' );
    $molecule->check();
  }

  public function testAppendAtomWithoutActionIsRejected (): void {
    $molecule = $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'A', $w, null, 'walletBundle', $w->bundle, new AtomMeta( [ 'name' => 'x' ] ) ) );
    $this->expectException( MetaMissingException::class );
    $this->expectExceptionMessage( 'isotopeA' );
    $molecule->check();
  }

  public function testAppendAtomVerifies (): void {
    $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'A', $w, null, 'walletBundle', $w->bundle, new AtomMeta( [ 'action' => 'append' ] ) ) )->check();
    $this->addToAssertionCount( 1 );
  }

  // Isotope M policies

  public function testMetaPolicyNamingAMissingKeyIsRejected (): void {
    $molecule = $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'M', $w, null, 'walletBundle', $w->bundle, new AtomMeta( [
      'name' => 'x',
      'writePolicy' => '{"missing":["all"]}',
    ] ) ) );
    $this->expectException( PolicyInvalidException::class );
    $molecule->check();
  }

  public function testMetaPolicyWithAnUnknownValueIsRejected (): void {
    $molecule = $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'M', $w, null, 'walletBundle', $w->bundle, new AtomMeta( [
      'name' => 'x',
      'readPolicy' => '{"name":["nobody"]}',
    ] ) ) );
    $this->expectException( PolicyInvalidException::class );
    $molecule->check();
  }

  public function testMetaPolicyVerifies (): void {
    $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'M', $w, null, 'walletBundle', $w->bundle, new AtomMeta( [
      'name' => 'x',
      'readPolicy' => '{"name":["all"]}',
      'writePolicy' => '{"name":["self","' . $w->bundle . '"]}',
    ] ) ) )->check();
    $this->addToAssertionCount( 1 );
  }

  // Isotope R in the JS Rule array form

  private static function jsRule (): array {
    return [
      [
        'condition' => [ [ 'key' => 'status', 'value' => 'active', 'comparison' => '===' ] ],
        'callback' => [ [ 'action' => 'reject' ] ],
      ],
    ];
  }

  public function testCreateRuleBuildsAJsFormatRuleThatVerifies (): void {
    $secret = self::secret();
    $source = new Wallet( $secret, 'USER', str_repeat( 'a7', 32 ) );
    $molecule = new Molecule( $secret, $source );
    $molecule->createRule( 'walletBundle', $source->bundle, self::jsRule() );
    $molecule->sign();
    $molecule->check();

    $rule = $molecule->getIsotopes( 'R' )[ 0 ]->aggregatedMeta()[ 'rule' ];
    $this->assertSame( '[{"condition":[{"key":"status","value":"active","comparison":"==="}],"callback":[{"action":"reject"}]}]', $rule );
  }

  public function testJsFormatRuleAtomVerifies (): void {
    $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'R', $w, null, 'walletBundle', $w->bundle, new AtomMeta( [
      'rule' => json_encode( self::jsRule(), JSON_THROW_ON_ERROR ),
    ] ) ) )->check();
    $this->addToAssertionCount( 1 );
  }

  /**
   * Literal = JSON.stringify(rules.map(Rule.toObject)) from JS src/instance/Rules/Rule.js for the
   * same input (unknown keys dropped, keys reordered, falsy optional keys omitted).
   */
  public function testRuleJsonMatchesJs (): void {
    $input = '[{"callback":[{"amount":"5","comparison":">=","token":"TEST","action":"collect","address":"a/b","extra":1},{"action":"meta","meta":{"x":"é","y":"1"},"metaId":"m","metaType":"t"}],"zz":1,"condition":[{"comparison":"===","value":"0","key":"status","extra":2}]},{"condition":[],"callback":[{"action":"reject"}]}]';
    $this->assertSame(
      '[{"condition":[{"key":"status","value":"0","comparison":"==="}],"callback":[{"action":"collect","address":"a/b","token":"TEST","amount":"5","comparison":">="},{"action":"meta","metaType":"t","metaId":"m","meta":{"x":"é","y":"1"}}]},{"condition":[],"callback":[{"action":"reject"}]}]',
      Rule::toJson( json_decode( $input, true, 512, JSON_THROW_ON_ERROR ) )
    );
  }

  public static function malformedRules (): array {
    return [
      'not a list' => [ '{"condition":[],"callback":[]}', MetaMissingException::class ],
      'empty list' => [ '[]', MetaMissingException::class ],
      'no callback' => [ '[{"condition":[]}]', MetaMissingException::class ],
      'incomplete condition' => [ '[{"condition":[{"key":"a","value":"b"}],"callback":[]}]', RuleArgumentException::class ],
      'callback without action' => [ '[{"condition":[],"callback":[{"metaType":"x"}]}]', RuleArgumentException::class ],
    ];
  }

  #[DataProvider( 'malformedRules' )]
  public function testMalformedRuleAtomIsRejected ( string $rule, string $exception ): void {
    $molecule = $this->signedUserMolecule( static fn( Wallet $w ) => Atom::create( 'R', $w, null, 'walletBundle', $w->bundle, new AtomMeta( [ 'rule' => $rule ] ) ) );
    $this->expectException( $exception );
    $molecule->check();
  }
}
