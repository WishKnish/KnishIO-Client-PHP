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

/**
 * Class ResponseMolecule
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseMolecule extends Response {

  /**
   * @var string
   */
  protected string $dataKey = 'data.ProposeMolecule';

  /**
   * @var mixed
   */
  protected mixed $payload;

  /**
   * Initialization
   */
  public function init (): void {

    // Get a json payload
    $payload_json = array_get( $this->data(), 'payload' );

    // Decode payload
    try {
      $this->payload = json_decode( $payload_json, true, 512, JSON_THROW_ON_ERROR );
    }
    catch ( JsonException ) {
      // Unable to decode JSON response?
      /** @TODO Add proper handing of JSON errors */
    }
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
   * @return string
   */
  public function getMolecularHash (): string {
    return array_get( $this->data(), 'molecularHash' );
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
   * @return string
   */
  public function status (): string {
    return array_get( $this->data(), 'status', 'rejected' );
  }

  /**
   * @return string
   */
  public function reason (): string {
    return array_get( $this->data(), 'reason', 'Invalid response from server' );
  }

  /**
   * @return mixed
   */
  public function payload (): mixed {
    return $this->payload;
  }

}
