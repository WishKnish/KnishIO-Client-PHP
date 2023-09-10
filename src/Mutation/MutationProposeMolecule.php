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

use JetBrains\PhpStorm\Pure;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Query\Query;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationProposeMolecule
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationProposeMolecule extends Query {

    // Query
    protected static string $defaultQuery = 'mutation( $molecule: MoleculeInput! ) { ProposeMolecule( molecule: $molecule )
		@fields
	}';

    // Fields
    protected array $fields = [
        'molecularHash',
        'height',
        'depth',
        'status',
        'reason',
        'payload',
        'createdAt',
        'receivedAt',
        'processedAt',
        'broadcastedAt',
    ];

    /**
     * @var bool
     */
    protected bool $isMutation = true;

    /**
     * @var Molecule
     */
    protected Molecule $molecule;

    /**
     * @var Wallet
     */
    protected Wallet $remainderWallet;

    /**
     * MutationProposeMolecule constructor.
     *
     * @param HttpClientInterface $client
     * @param Molecule $molecule
     * @param string|null $query
     *
     */
    public function __construct ( HttpClientInterface $client, Molecule $molecule, string $query = null ) {
        parent::__construct( $client, $query );

        // Create a molecule
        $this->molecule = $molecule;
    }

    /**
     * @return Molecule
     */
    public function molecule (): Molecule {
        return $this->molecule;
    }

    /**
     * @return Wallet
     */
    public function remainderWallet (): Wallet {
        return $this->remainderWallet;
    }

    /**
     * @param array $variables
     *
     * @return array
     */
    #[Pure]
    public function compiledVariables ( array $variables ): array {
        // Default variables
        $variables = parent::compiledVariables( $variables );

        // Merge variables with a molecule key
        return array_merge( $variables, [ 'molecule' => $this->molecule ] );
    }

    /**
     * @param string $response
     *
     * @return ResponseMolecule
     * @throws KnishIOException
     */
    public function createResponse ( string $response ): ResponseMolecule {
        return new ResponseMolecule( $this, $response );
    }

}
