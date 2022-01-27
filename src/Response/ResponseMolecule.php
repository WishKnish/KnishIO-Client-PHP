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

namespace WishKnish\KnishIO\Client\Response;

use JsonException;
use WishKnish\KnishIO\Client\MoleculeStructure;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMoleculeStructure;
use function json_decode;

/**
 * Class ResponseMolecule
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseMolecule extends Response {
  protected string $dataKey = 'data.ProposeMolecule';

  protected mixed $payload;

  protected MoleculeStructure $clientMolecule;

  /**
   * Response constructor.
   *
   * @param MutationProposeMoleculeStructure|null $query
   * @param string $json
   *
   * @throws JsonException
   */
  public function __construct ( ?MutationProposeMoleculeStructure $query, string $json ) {
    parent::__construct( $query, $json );

    if ( $query !== null ) {
      $this->clientMolecule = $query->moleculeStructure();
    }
  }

  public function init (): void {

    // Get a json payload
    $payload_json = array_get( $this->data(), 'payload' );

    // Decode payload
    try {
      $this->payload = json_decode( $payload_json, true, 512, JSON_THROW_ON_ERROR );
    }
    catch ( JsonException $e ) {
      // Unable to decode JSON response?
      /** @TODO Add proper handing of JSON errors */
    }
  }

  /**
   * Get a client molecule
   *
   * @return MoleculeStructure
   */
  public function clientMolecule (): MoleculeStructure {
    return $this->clientMolecule;
  }

  /**
   * @return MoleculeStructure|null
   */
  public function molecule (): ?MoleculeStructure {
    if ( !$data = $this->data() ) {
      return null;
    }

    $molecule = new MoleculeStructure();
    $molecule->molecularHash = array_get( $data, 'molecularHash' );
    $molecule->status = array_get( $data, 'status' );
    $molecule->createdAt = array_get( $data, 'createdAt' );

    return $molecule;
  }

  /**
   * Success?
   *
   * @return bool
   */
  public function success (): bool {
    return ( $this->status() === 'accepted' );
  }

  /**
   * @return mixed
   */
  public function status (): mixed {
    return array_get( $this->data(), 'status', 'rejected' );
  }

  /**
   * @return mixed
   */
  public function reason (): mixed {
    return array_get( $this->data(), 'reason', 'Invalid response from server' );
  }

  /**
   * @return mixed
   */
  public function payload (): mixed {
    return $this->payload;
  }

}
