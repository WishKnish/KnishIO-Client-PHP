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

namespace WishKnish\KnishIO\Client\Tests;

// !!! @todo: this unit test must to be separated from any server side (it should work as an independent part) !!!
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class CounterpartyDataTest
 * @package WishKnish\KnishIO\Client\Tests
 */
class CounterpartyDataTest extends TestCase {

  private array $counterparty = [];

  private string $source_secret;
  private Wallet $source_wallet;

  /**
   * @throws Exception
   * @throws GuzzleException
   */
  public function beforeExecute ():void {
    parent::beforeExecute();

    // Source secret & wallet
    $this->source_secret = Crypto::generateSecret();
    $this->source_wallet = new Wallet ( $this->source_secret );

    // Create counterparty secret & authenticate it to add bundle hash to DB
    $counterparty_secret = Crypto::generateSecret();
    $this->client( $counterparty_secret );

    // Init counterparties
    $this->counterparty[] = null; // Without counterparty
    $this->counterparty[] = 'counterparty_slug'; // Is a cell slug
    $this->counterparty[] = Crypto::generateBundleHash( $counterparty_secret ); // Is a bundle hash (other user)
  }

  /**
   * @throws Exception|GuzzleException
   */
  public function testCounterpartyData ():void {
    $this->beforeExecute();

    // Create meta for each counterparty
    foreach ( $this->counterparty as $key => $counterparty ) {
      $this->createCounterpartyMeta( $this->source_secret, $counterparty, 'metaType', 'metaId', [ "key{$key}_1" => "value{$key}_1", "key{$key}_2" => "value{$key}_2" ] );
    }

  }

  /**
   * @throws Exception|GuzzleException
   */
  protected function createCounterpartyMeta ( $secret, $counterparty, $metaType, $metaId, $metas ): void {
    // Create a meta molecule with a counterparty
    $molecule = $this->client( $secret )
        ->createMolecule()
        ->withCounterparty( $counterparty );

    // Init meta, sign & check
    $molecule->initMeta( $metas, $metaType, $metaId );
    $molecule->sign();
    $molecule->check();

    // Execute query & check response
    $this->executeMolecule( $this->source_secret, $molecule );

  }

}
