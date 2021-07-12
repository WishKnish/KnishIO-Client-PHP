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

use WishKnish\KnishIO\Client\MoleculeStructure;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMoleculeStructure;
use WishKnish\KnishIO\Client\Query\Query;
use function json_decode;

/**
 * Class ResponseMolecule
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseMolecule extends Response {
  protected $dataKey = 'data.ProposeMolecule';

  protected $payload;

  protected $clientMolecule;

  /**
   * Response constructor.
   *
   * @param Query $query
   * @param string $json
   */
  public function __construct ( ?MutationProposeMoleculeStructure $query, $json ) {
    parent::__construct( $query, $json );

    if ( $query !== null ) {
      $this->clientMolecule = $query->moleculeStructure();
    }
  }

  /**
   *
   */
  public function init () {

    // Get a json payload
    $payload_json = array_get( $this->data(), 'payload' );

    // Decode payload
    $this->payload = json_decode( $payload_json, true );
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
  public function molecule () {
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
   * @return mixed
   */
  public function success () {
    return ( $this->status() === 'accepted' );
  }

  /**
   * @return mixed
   */
  public function status () {
    return array_get( $this->data(), 'status', 'rejected' );
  }

  /**
   * @return mixed
   */
  public function reason () {
    return array_get( $this->data(), 'reason', 'Invalid response from server' );
  }

  /**
   * @return mixed|null
   */
  public function payload () {
    return $this->payload;
  }

}
