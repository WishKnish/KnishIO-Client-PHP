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

namespace Console\Command;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use WishKnish\KnishIO\Client\KnishIO;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Client\KnishIOClient;

/**
 * Class MoleculeTransfer
 * @package Console\Command
 */
class MoleculeTransfer extends Command {

  /**
   * @var string
   */
  protected $signature = 'molecule:transfer';

  /**
   * @var string
   */
  protected $description = 'Check token transfer';

  /**
   * @var string
   */
  protected $secret = '509d8d7eb52af57c17c01c6882b1599b2d4c9eed5fe99e33300c6c54a2b22cfe1c3bba65a63bca1a53436fadc59e19a65f75b8be4e0d71ea523691f00a9ea0df5a4b1d25d018b1a35b2ba9e29b897ce42db441902b56b7dbce5e3288c70e4bd3da3d0db97182bce4e9f2e5edde58a475cf5c5acb07c97efd5fcd876694e969d456a910b36b109915df47186b00d1ac3a8fd3bdd5a670947dc7c4cd9d575c960f4a60991c4c65fae9670a3f5f4e45339ea7380357161b44cb898c93b9b5ca4fdeebe10667cec1d299a104fe6429838e921cd1504db935caccb4ac65208b89527bfa2020844b55f8b1a53e5913fa9c03ab18605d18d8ab48e9283595c6a7ea727410cbf8b06dd988998c2634ef49f22d792a3aedf6bd55b1bf593f02d13c61a88799d610c6a10b498ed1c8843b67faa5d199de44a0a70e55ff3003c302f5fe3d043ddd54b0c09e4fbcc51eba2b76c2ada99b1eea3e58142505631c9a987c93689ab3421f2d2e6f4085870aee3efd4284e5459d654841ee7ca6002119d61386655ecae1852ed66cfac7259560db472cbeffc472f5e75f7756e6b6915072152cc81ebb1cc4ef57cbf245666bdf14f72196146e7301cd4ddefb9e759b7d25dbd0b08260710368c26d68c0774139284071c6e829d417f0b7c02a87a38d980ecfabfe10791239aef1567d13bd11309245ab1b851a7734c751fdc9448d9da95262ee246ffc09bf5559aaa47947ab0d25c0213c2646a1cc0fc3faebbc461d51eafcb0782ae611002c7c86511fbde3c6017328a9753c3066172ed2d49c6465c157384be279e65a180b242ecd1e3b8dc37e005fd3df90e62943d36732a1996739b770f6f7c614d46b17e8924d245640cdb59b8033e0c468ec68a966373f222c1b298a67819268fd9c9f59331c8bf788e8d63db1706ce9cf1d4c4b335c59a21893b9866fd70c54bc180fa9828053499505b3048e9aaee619cc605adfe329ded4fd0c08b17e1b2d840b446390c75186fd9e4b6b03b345b91ee7d3b0b855c1bc9a6223d1d38f98fa96c37120674082fba8aaa17766fdaf50e89a9b3d9dfc26ac9e420b46a3bc523a5634f60e28de683cad562d2aa4edbd2d9c3e755ce776fe85a80a6ff3943beec445a02a95c69f44aeca4e401a0eb5f08915812c08831e535383934eef9101feedd28e9b4f02b08291c33e279a783cc2a5eaed92368d02a9da929433ae43edb64383b36a938c360e85f0339c3410342d2eb901e65498e1e89782561a4f961cae3d2d3063a48f2dfd08c27ec5ff4ed103c4c77ae7803ad386a477d2b5e0eef1b51db3909f95ccb921669053a2cb51f6233f3b6933db50f81e747a2e3045b93b8c5cdf5f3b17110c8a47a0f9f9d0558096833717fb7dcfcaefd0406982e51cdc1aaf91d9c149a29fbc2f7e9a0c682d42e4be211b1eb0a82d6ce1117ade59c3890f';

  /**
   * @var string
   */
  protected $another = 'c5da568b4656b5305676cc0ee26c24460ed0c27df013f86717db2f19d9205a35c8e01daeb22bc9f4ded2db7e6ced0dc9c0f5330cc22e4b35f8dcccb7a1b8043468c2aa60762c5a90e272b7a699eb42be7cdb97f522d0595cf1c92b81a9b0c377cfd1c8bd3ed0ff742cab311d24e6a7f2315552f78c6800fd21b24aa3092fd03d570f047948b9181fcf2a3eca919188f9d8c3b65d631379f8e7b02c32deb4f5916faffbc022ceb45c4d9239e548d01d331fb7bf437e679ed679619e2c46161984a21d3ecc63be284147d7f104555f7e1f220ec92a8ef1f6935c1b29ddde51f7b4f1c45c2f51cca084d1e7ae616828543ad1b154df9fb198570af24e351126c0cdd6dc4e5cbefaec82a58016e078a00fa0cd598000b5bb8760cf5120d69b5b2a5f16a7ead6416367080076e85769f56c11a056de2c0c3243bdc4f507c92bcba4f26ff041eafc1999fc05dca8b308f918b00ee0cae74c1f0f539809ca750a21785ee21a1c24bcef2a9727a8b98fe47b5f5010fafe061365331731c69c22c06846167e8cc79bf8613902ea136c96ac775d9f90f99bba404bd4af83bbf3e20d048ccf7304f5e55edbc5d51da7f6148d3e4bb77e3b3f202d4b5c73e69039c9b9ff02ea6d3297512841b73a02c83f6dce061c7e9eb9da79100bad80738a3d2d4a8d0a8fcd9bc9cf4ef432b0e1d74ec8ea3d4a9882a7f6ea159f060b05c9038552a2764d965e4f06f629d62b0b39669839879372c64471697867d9235843affc9bf118744b18de8f021d804bda26c578232a82d91bec493c08202c96d863ebe6a123d452b95e4eb9bd9c23e49293d143fddf011d6d4e36d43d1ba77fc22a9f39dc94d9c1f1e44284df4fca8c2d6e7e2e7e9551256bde7022dc9da4d4b41b8e8a2cf3840558af13be976c06008b1165c03074d2cc7ab3d1604665fa0e8320a81b6d7090afc27c311941ff61bc64c8d90945e99bd64389568cd84733aa6ac085ec15ec82dd746cd791a21c9e38a74b1d6f5e6eca4f0edf6f7c7789895b6a6705310d35fba6d31efa96f7b9d7583f5be3e9457d0e6b8d908423565b4f89798a73b74e5de4cc51b8d6af1e1754f512141cdca3969fa009959ebb96244f3c3e3c774c754e6cef1cca6e992b2e140227fb6dec536fdf9e84e40ba8ce59a723c1f08a01039fc8726705a59d2f4ec5a07c61ef8232b0b19647b19bb4992c17db1f57904dc64ea73a45f892f6d50821df7f40aa629f0659a9fa78c9cad282758c4a8c6cfd8b0b5d91134249b8c0072c10216171a82875454ab75b096b420ac25c6bd1cac93e9b1ade5da37dbcf0f939314ffa102cfda5ae3e98a4db0999338b4df39887e973ac7d8d1ca5b772728267b372a518587854b1a01387509717c6f6ba17b5dc02019ff24edb3759ec84e22eb615f5510614acbda0bca1164390cc0a061b87c3437d3ee0f0';

  /**
   * @throws Exception
   */
  public function handle () {
    //KnishIO::setUrl( 'https://subbox.loc/graphql' );

    /*$token = 'FOJ';
    $another = new Wallet( $this->another, $token );
    $response = KnishIO::transferToken( $this->secret, $another, $token, 15 );
    dump($response);*/

    $data = [ 'Мама' => 'мыла раму', ];

    $wallet = new Wallet( $this->secret );
    $wallet2 = new Wallet( $this->another );
    $molecule = new Molecule();

    //$encrypt = $wallet->encryptMyMessage( $data, $wallet->pubkey, $wallet2->pubkey );

    //$molecule->initMeta( $wallet, $encrypt, null, null );
    //$molecule->sign( $this->secret );

    /*$client = new Client( [
        'base_uri'    => 'https://subbox.loc',
        'verify'      => false,
        'http_errors' => false,
        'headers'     => [
            'User-Agent' => 'KnishIO/0.1',
            'Accept'     => 'application/json',
        ]
    ] );

    $response = $client->post( 'graphql', [
        'json' => [
            'query'     => 'mutation( $molecule: MoleculeInput! ) { ProposeMolecule( molecule: $molecule, ) { molecularHash, height, depth, status, reason, reasonPayload, createdAt, receivedAt, processedAt, broadcastedAt } }',
            'variables' => [
                'molecule' => $molecule,
            ],
        ]
    ] );

    dump( $response );

    $response = $client->post( 'graphql', [
        'json' => [
            'query'     => 'mutation( $molecule: MoleculeInput! ) { ProposeMolecule( molecule: $molecule, ) { molecularHash, height, depth, status, reason, reasonPayload, createdAt, receivedAt, processedAt, broadcastedAt } }',
            'variables' => [
                'molecule' => $molecule,
            ],
        ]
    ] );*/

    $client = new KnishIOClient( 'https://subbox.loc/graphql' );
    dump( $client->createToken( $this->another, 'KNISHQ', 50000, [ 'name' => 'KNISHQ token', 'fungibility' => 'fungible', 'supply' => 'replenishable', 'decimals' => 2 ] )
        ->payload() );
  }
}
