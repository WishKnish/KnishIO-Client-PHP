<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Mutation;

use WishKnish\KnishIO\Client\Response\ResponseMetaCreate;

/**
 * Class MutationCreateMeta
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationCreateMeta extends MutationProposeMolecule
{

  /**
   * @param $type
   * @param $contact
   * @param $code
   * @throws \Exception
   */
  public function fillMolecule ( string $metaType, string $metaId, array $metadata )
  {
    $this->molecule->initMeta( $metadata, $metaType, $metaId );
    $this->molecule->sign();
    $this->molecule->check();
  }

  /**
   * @param $response
   *
   * @return ResponseMetaCreate|\WishKnish\KnishIO\Client\Response\ResponseMolecule
   */
  public function createResponse ( $response ) {
    return new ResponseMetaCreate( $this, $response );
  }


}
