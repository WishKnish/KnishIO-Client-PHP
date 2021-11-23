<?php
/*
                               (
                              (/(
                              (//(
                              (///(
                             (/////(
                             (//////(                          )
                            (////////(                        (/)
                            (////////(                       (///)
                           (//////////(                      (////)
                           (//////////(                     (//////)
                          (////////////(                    (///////)
                         (/////////////(                   (/////////)
                        (//////////////(                  (///////////)
                        (///////////////(                (/////////////)
                       (////////////////(               (//////////////)
                      (((((((((((((((((((              (((((((((((((((
                     (((((((((((((((((((              ((((((((((((((
                     (((((((((((((((((((            ((((((((((((((
                    ((((((((((((((((((((           (((((((((((((
                    ((((((((((((((((((((          ((((((((((((
                    (((((((((((((((((((         ((((((((((((
                    (((((((((((((((((((        ((((((((((
                    ((((((((((((((((((/      (((((((((
                    ((((((((((((((((((     ((((((((
                    (((((((((((((((((    (((((((
                   ((((((((((((((((((  (((((
                   #################  ##
                   ################  #
                  ################# ##
                 %################  ###
                 ###############(   ####
                ###############      ####
               ###############       ######
              %#############(        (#######
             %#############           #########
            ############(              ##########
           ###########                  #############
          #########                      ##############
        %######

        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Mutation;

use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\MoleculeStructure;
use WishKnish\KnishIO\Client\Query\Query;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;

/**
 * Class MutationProposeMoleculeStructure
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationProposeMoleculeStructure extends Query {
  // Query
  protected static string $default_query = 'mutation( $molecule: MoleculeInput! ) { ProposeMolecule( molecule: $molecule )
		@fields
	}';

  // Fields
  protected array $fields = [ 'molecularHash', 'height', 'depth', 'status', 'reason', 'payload', 'createdAt', 'receivedAt', 'processedAt', 'broadcastedAt', ];

  // Molecule
  protected MoleculeStructure $moleculeStructure;

  /**
   * MutationProposeMoleculeStructure constructor.
   *
   * @param HttpClientInterface $client
   * @param MoleculeStructure $moleculeStructure
   * @param string|null $query
   */
  public function __construct ( HttpClientInterface $client, MoleculeStructure $moleculeStructure, string $query = null ) {
    parent::__construct( $client, $query );

    // Create a molecule
    $this->moleculeStructure = $moleculeStructure;
  }

  /**
   * @param array|null $variables
   *
   * @return array
   */
  public function compiledVariables ( array $variables = null ): array {
    // Default variables
    $variables = parent::compiledVariables( $variables );

    // Merge variables with a molecule key
    return array_merge( $variables, [ 'molecule' => $this->moleculeStructure ] );
  }

  /**
   * @return MoleculeStructure
   */
  public function moleculeStructure (): MoleculeStructure {
    return $this->moleculeStructure;
  }

  /**
   * @param string $response
   *
   * @return ResponseMolecule
   */
  public function createResponse ( string $response ): ResponseMolecule {
    return new ResponseMolecule( $this, $response );
  }

}
