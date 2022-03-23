<?php
namespace WishKnish\KnishIO\Client\Mutation;

use Exception;
use JsonException;
use WishKnish\KnishIO\Client\Response\ResponseCreateRule;

class MutationCreateRule extends MutationProposeMolecule {
  /**
   * @throws JsonException|Exception
   */
  public function fillMolecule ( string $metaType, string $metaId, array $rule, array $policy ): void {
    $this->molecule->createRule( $metaType, $metaId, $rule, $policy );
    $this->molecule->sign();
    $this->molecule->check();

  }

  public function createResponse ( $response ): ResponseCreateRule {
    return new ResponseCreateRule( $this, $response );
  }
}
