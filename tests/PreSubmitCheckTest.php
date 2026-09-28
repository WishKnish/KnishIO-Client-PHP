<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use WishKnish\KnishIO\Client\Atom;
use WishKnish\KnishIO\Client\AtomMeta;
use WishKnish\KnishIO\Client\Exception\MoleculeAtomsMissingException;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
use WishKnish\KnishIO\Client\Wallet;

/** A molecule builder that "forgets" the ContinuID atom. */
final class MoleculeWithoutContinuId extends Molecule {
  public function addContinuIdAtom (): Molecule {
    return $this;
  }
}

/** A client whose molecules come from MoleculeWithoutContinuId. */
final class ClientWithoutContinuId extends KnishIOClient {
  public function createMolecule ( ?string $secret = null, ?Wallet $sourceWallet = null, ?Wallet $remainderWallet = null ): Molecule {
    $molecule = parent::createMolecule( $secret, $sourceWallet, $remainderWallet );
    return new MoleculeWithoutContinuId( $molecule->secret(), $molecule->sourceWallet(), $molecule->remainderWallet(), $molecule->cellSlug );
  }
}

/**
 * Contract 9.7: a molecule a high-level client operation builds is checked before it is sent, so
 * a USER-signed molecule without its ContinuID atom never reaches the validator (where it would
 * consume the pointer key). The raw ProposeMolecule path sends a caller-built molecule unchecked.
 */
class PreSubmitCheckTest extends TestCase {

  private const POINTER = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

  private static function secret (): string {
    return Crypto::generateSecret( 'php-pre-submit-check-test' );
  }

  private static function continuIdBody (): string {
    $secret = self::secret();
    return json_encode( [ 'data' => [ 'ContinuId' => [
      'type' => 'regular',
      'address' => ( new Wallet( $secret, 'USER', self::POINTER ) )->address,
      'bundleHash' => Crypto::generateBundleHash( $secret ),
      'tokenSlug' => 'USER',
      'position' => self::POINTER,
      'batchId' => null,
      'characters' => 'BASE64',
      'pubkey' => null,
      'amount' => '0',
      'createdAt' => '2026-09-28T00:00:00Z',
    ] ] ], JSON_THROW_ON_ERROR );
  }

  public function testHighLevelOperationRefusesAMoleculeMissingItsContinuIdAtom (): void {
    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [ self::continuIdBody() ] );
    $client = new ClientWithoutContinuId( 'http://offline.invalid/graphql', $http );
    $client->setSecret( self::secret() );

    try {
      $client->createMeta( 'walletBundle', Crypto::generateBundleHash( self::secret() ), [ 'appRole' => 'admin' ] );
      $this->fail( 'a USER-signed meta molecule without an I atom must be refused client-side' );
    }
    catch ( MoleculeAtomsMissingException $e ) {
      $this->assertStringContainsString( 'ContinuID', $e->getMessage() );
    }

    $this->assertCount( 1, $http->requests, 'only the ContinuID query is sent' );
    $this->assertStringNotContainsString( 'ProposeMolecule(', (string) $http->requests[ 0 ]->getBody() );
  }

  public function testRawProposeMoleculeSendsACallerBuiltMoleculeUnchanged (): void {
    $secret = self::secret();
    $http = new RecordingHttpClient( 'http://offline.invalid/graphql', [
      json_encode( [ 'data' => [ 'ProposeMolecule' => [
        'molecularHash' => str_repeat( 'a', 64 ), 'height' => 0, 'depth' => 0, 'status' => 'rejected',
        'reason' => 'Molecule payload validation failed: AtomsMissing', 'payload' => null, 'createdAt' => '2026-09-28T00:00:00Z',
      ] ] ], JSON_THROW_ON_ERROR ),
    ] );
    $client = new KnishIOClient( 'http://offline.invalid/graphql', $http );
    $client->setSecret( $secret );

    $userWallet = new Wallet( $secret, 'USER', self::POINTER );
    $molecule = new Molecule( $secret, $userWallet, null, 'rawtest' );
    $molecule->addAtom( Atom::create( 'M', $userWallet, null, 'walletBundle', $userWallet->bundle, new AtomMeta( [ 'appRole' => 'admin' ] ) ) );
    $molecule->sign();

    $client->createMoleculeMutation( MutationProposeMolecule::class, $molecule )->execute();

    $this->assertCount( 1, $http->requests );
    $sent = json_decode( (string) $http->requests[ 0 ]->getBody(), true, 512, JSON_THROW_ON_ERROR )[ 'variables' ][ 'molecule' ];
    $this->assertSame( $molecule->molecularHash, $sent[ 'molecularHash' ] );
    $this->assertSame( [ 'M' ], array_column( $sent[ 'atoms' ], 'isotope' ), 'the M-only molecule is sent as built' );
  }
}
