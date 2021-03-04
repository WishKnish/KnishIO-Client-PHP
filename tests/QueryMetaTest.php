<?php

namespace WishKnish\KnishIO\Client\Tests;


use Illuminate\Support\Arr;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Query\QueryMetaInstance;
use WishKnish\KnishIO\Client\Query\QueryMetaType;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;
use WishKnish\KnishIO\Client\Wallet;



// !!! @todo: this unit test must to be separated from any server side (it should work as an independent part) !!!

/*

Create batch metas test code.

$metaType = 'batch';
$allMetas = [
  'first' => [],
  'second' => [],
  'third' => [],
  'fourth' => [],
  'fifth' => [],
];
$secret = \WishKnish\KnishIO\Client\Libraries\Crypto::generateSecret();
$client = new \WishKnish\KnishIO\Client\KnishIOClient();
$client->requestAuthToken($secret);
foreach($allMetas as $metaId => $metaData) {
  if ($metaId === 'first') {
    continue;
  }
  $metaData = array_merge($metaData, [
    'key' => $metaId,
    'key_'.$metaId => 'value_'.$metaId,
  ]);
  $response = $client->createMeta($metaType, $metaId, $metaData);
}
die('Ok');

*/

/**
 * Class QueryMetaTest
 * @package WishKnish\KnishIO\Client\Tests
 */
class QueryMetaTest extends TestCase
{

	protected $source_secret;
	protected $source_wallet;

	protected $guzzle_client;


	/**
	 * @throws \Exception
	 */
	public function beforeExecute()
	{
		// $this->cell_slug = null;
		// $this->graphql_url = 'https://frontrow.knish.io/graphql';

		parent::beforeExecute();

		// Source secret & wallet
		$this->source_secret = Crypto::generateSecret();
		$this->source_wallet = new Wallet ($this->source_secret);

		// Guzzle client from the KnishIOClient object
		$this->guzzle_client = $this->client($this->source_secret)->client();
	}

  /**
   * @return Wallet
   * @throws \Exception
   */
	public function sourceWallet () {
		return new Wallet ($this->source_secret);
	}


	/**
	 * Clear data test
	 *
	 * @throws \ReflectionException
	 */
	public function testClearAll () {

		// Initial code
		$this->beforeExecute();

		// Call server cleanup
		$this->callServerCleanup(\WishKnish\KnishIO\Tests\QueryServerTest::class);

		// Deafult assertion
		$this->assertEquals(true, true);
	}

  /**
   * @throws \ReflectionException
   */
	public function testCreateMetas () {

		$this->beforeExecute();

		// Closure to create metas
    $self = $this;
		$createMetasClosure = static function ( $metaType, $metaId, $metas ) use ( $self ) {

      $molecule = $self->client( $self->source_secret )->createMolecule();
      $molecule->initMeta( $metas, $metaType, $metaId );
      $molecule->sign();
      $molecule->check();

      $self->executeMolecule( $self->source_secret, $molecule );

    };

    // objectId => Product metaID
    $createMetasClosure ('ProductEdit', 'product1', [
				'amount' => '1.555',
				'status' => 'active',
				'objectId' => 'id1',
    ]);
    $createMetasClosure ('ProductEdit', 'product1', [
        'amount' => '1.222',
        'status' => 'inactive',
        'objectId' => 'id1',
    ]);
    $createMetasClosure ('ProductEdit', 'product1', [
        'amount' => '1.111',
        'status' => 'archived',
        'objectId' => 'id1',
    ]);
    // product2
    $createMetasClosure ('ProductEdit', 'product2', [
      'amount' => '1.111',
      'status' => 'archived',
      'objectId' => 'id2',
    ]);
    $createMetasClosure ('ProductEdit', 'product2', [
      'amount' => '1.222',
      'status' => 'inactive',
      'objectId' => 'id2',
    ]);
    $createMetasClosure ('ProductEdit', 'product2', [
      'amount' => '1.555',
      'status' => 'active',
      'objectId' => 'id2',
    ]);
    // product3
    $createMetasClosure ('ProductEdit', 'product3', [
      'amount' => '3.999',
      'status' => 'active',
      'objectId' => 'id3',
    ]);
    $createMetasClosure ('ProductEdit', 'product3', [
      'amount' => '3.222',
      'status' => 'inactive',
      'objectId' => 'id3',
    ]);
    $createMetasClosure ('ProductEdit', 'product3', [
      'amount' => '0.999',
      'status' => 'active',
      'objectId' => 'id3',
    ]);



    // --- Fave
    // Product1
    $createMetasClosure ('ProductFave', 'product1user1', [
      'status' => 'inactive',
      'objectId' => 'id1',
    ]);
    $createMetasClosure ('ProductFave', 'product1user1', [
      'status' => 'active',
      'objectId' => 'id1',
    ]);
    $createMetasClosure ('ProductFave', 'product1user2', [
      'status' => 'archived',
      'objectId' => 'id1',
    ]);
    $createMetasClosure ('ProductFave', 'product1user2', [
      'status' => 'active',
      'objectId' => 'id1',
    ]);
    $createMetasClosure ('ProductFave', 'product1user3', [
      'status' => 'inactive',
      'objectId' => 'id1',
    ]);
    $createMetasClosure ('ProductFave', 'product1user3', [
      'status' => 'active',
      'objectId' => 'id1',
    ]);

    // Product2
    $createMetasClosure ('ProductFave', 'product2user1', [
      'status' => 'active',
      'objectId' => 'id2',
    ]);
    $createMetasClosure ('ProductFave', 'product2user1', [
      'status' => 'inactive',
      'objectId' => 'id2',
    ]);
    $createMetasClosure ('ProductFave', 'product2user2', [
      'status' => 'active',
      'objectId' => 'id2',
    ]);
    $createMetasClosure ('ProductFave', 'product2user2', [
      'status' => 'archived',
      'objectId' => 'id2',
    ]);
    $createMetasClosure ('ProductFave', 'product2user3', [
      'status' => 'inactive',
      'objectId' => 'id2',
    ]);
    $createMetasClosure ('ProductFave', 'product2user3', [
      'status' => 'active',
      'objectId' => 'id2',
    ]);

    // Product3
    $createMetasClosure ('ProductFave', 'product3user1', [
      'status' => 'inactive',
      'objectId' => 'id3',
    ]);
    $createMetasClosure ('ProductFave', 'product3user1', [
      'status' => 'active',
      'objectId' => 'id3',
    ]);
    $createMetasClosure ('ProductFave', 'product3user2', [
      'status' => 'archived',
      'objectId' => 'id3',
    ]);
    $createMetasClosure ('ProductFave', 'product3user2', [
      'status' => 'active',
      'objectId' => 'id3',
    ]);
    $createMetasClosure ('ProductFave', 'product3user3', [
      'status' => 'active',
      'objectId' => 'id3',
    ]);
    $createMetasClosure ('ProductFave', 'product3user3', [
      'status' => 'inactive',
      'objectId' => 'id3',
    ]);

	}


	/**
	 * @throws \ReflectionException
	 */
	public function testMetaTypeQuery () {

		$this->beforeExecute();



		// Closure to use new query every time
		$guzzle_client = $this->guzzle_client;
		$newQuery = static function () use ( $guzzle_client ) {
		  return new QueryMetaType( $guzzle_client );
    };




		// status=ACTIVE | ALL
		$response = $newQuery()->execute(['metaType' => 'ProductEdit',
        'latestMetas' => false,
        'filter' => [
          [
            'key' => 'status',
            'value' => 'active',
          ],
      ],
    ]);
		$this->assertEquals($this->getLimitedResult($response), [
			[
				'metaType' => 'ProductEdit',
				'instances' => [
          ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
					['metaType' => 'ProductEdit', 'metaId' => 'product2'],
          ['metaType' => 'ProductEdit', 'metaId' => 'product1'],
				],
			],
		]);
    // status=ACTIVE | LATEST
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
        ],
      ],
    ]);
    $this->assertEquals($this->getLimitedResult($response), [
      [
        'metaType' => 'ProductEdit',
        'instances' => [
          ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
          ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
        ],
      ],
    ]);


    // ORDER BY
    // -- desc
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'queryArgs' => [
        'orderBy' => 'amount',
        'order' => 'desc',
      ],
    ]);
    $this->assertEquals($this->getLimitedResult($response), [
      [
        'metaType' => 'ProductEdit',
        'instances' => [
          ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
          ['metaType' => 'ProductEdit', 'metaId' => 'product1'],
          ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
        ],
      ],
    ]);
    // -- asc
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'queryArgs' => [
        'orderBy' => 'amount',
        'order' => 'asc',
      ],
    ]);
    $this->assertEquals($this->getLimitedResult($response), [
      [
        'metaType' => 'ProductEdit',
        'instances' => [
          ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
          ['metaType' => 'ProductEdit', 'metaId' => 'product1'],
          ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
        ],
      ],
    ]);



    // -- AND WHERE
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
          'comparison' => '=',
        ],
        [
          'key' => 'objectId',
          'value' => 'id3',
          'comparison' => '=',
        ],
      ],
    ]);
    $this->assertEquals($this->getLimitedResult($response), [
      [
        'metaType' => 'ProductEdit',
        'instances' => [
          ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
        ],
      ],
    ]);


    // -- WHERE LIKE
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
          'comparison' => '=',
        ],
        [
          'key' => 'objectId',
          'value' => '2',
          'comparison' => '=~',
          'criterion' => 'OR',
        ],
        [
          'key' => 'objectId',
          'value' => '1',
          'comparison' => '=~',
          'criterion' => 'OR',
        ],
      ],
    ]);
    $this->assertEquals($this->getLimitedResult($response), [
      [
        'metaType' => 'ProductEdit',
        'instances' => [
          ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
        ],
      ],
    ]);



    // Pagination 1
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'queryArgs' => [
        'offset' => 1,
        'limit' => 1,
        'orderBy' => 'amount',
        'order' => 'desc',
      ],
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
          'comparison' => '=',
        ],
      ],
    ], [
      'metaType', 'instances' => ['metaType', 'metaId'], 'paginatorInfo' => ['currentPage', 'total'],
    ]);
    $this->assertEquals($response->data(), [
      [
        'metaType' => 'ProductEdit',
        'instances' => [
          ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
        ],
        'paginatorInfo' => [
          'currentPage' => 1, 'total' => 2,
        ],
      ],
    ]);
    // Pagination 2
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'queryArgs' => [
        'offset' => 2,
        'limit' => 1,
        'orderBy' => 'amount',
        'order' => 'desc',
      ],
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
          'comparison' => '=',
        ],
      ],
    ],[
      'metaType', 'instances' => ['metaType', 'metaId'], 'paginatorInfo' => ['currentPage', 'total'],
    ]);
    $this->assertEquals($response->data(), [
      [
        'metaType' => 'ProductEdit',
        'instances' => [
          ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
        ],
        'paginatorInfo' => [
          'currentPage' => 2, 'total' => 2,
        ],
      ],
    ]);
    // Pagination 3
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'queryArgs' => [
        'offset' => 2,
        'limit' => 1,
        'orderBy' => 'amount',
        'order' => 'asc',
      ],
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
          'comparison' => '=',
        ],
      ],
    ],[
      'metaType', 'instances' => ['metaType', 'metaId'], 'paginatorInfo' => ['currentPage', 'total'],
    ]);
    $this->assertEquals($response->data(), [
      [
        'metaType' => 'ProductEdit',
        'instances' => [
          ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
        ],
        'paginatorInfo' => [
          'currentPage' => 2, 'total' => 2,
        ],
      ],
    ]);



    // Count => EDIT
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'countBy' => '*',
    ], [
      'metaType',
      'instanceCount' => ['key', 'value'],
    ]);
    $this->assertEquals($response->data(), [
      [
        'metaType' => 'ProductEdit',
        'instanceCount' => [
          ['key' => '*', 'value' => 3]
        ],
      ],
    ]);
    // Count => Fave
    $response = $newQuery()->execute(['metaType' => 'ProductFave',
      'countBy' => '*',
    ], [
      'metaType',
      'instanceCount' => ['key', 'value'],
    ]);
    $this->assertEquals($response->data(), [
      [
        'metaType' => 'ProductFave',
        'instanceCount' => [
          ['key' => '*', 'value' => 9]
        ],
      ],
    ]);


    // CountBy objectId with filter, order & pagination
    $response = $newQuery()->execute(['metaType' => 'ProductFave',
      'countBy' => 'objectId',
      'latestMetas' => true,
      'filter' => [
        [
          "key" => "status",
          "value" => "active"
        ],
        [
          "key" => "objectId",
          "value" => "id1",
          "comparison" => "=",
          "criterion" => "OR"
        ],
        [
          "key" => "objectId",
          "value" => "id2",
          "comparison" => "=",
          "criterion" => "OR"
        ],
      ],
    ],[
      'metaType',
      'instanceCount' => ['key', 'value'],
    ]);
    $this->assertEquals($response->data(), [
      [
        'metaType' => 'ProductFave',
        'instanceCount' => [
          ['key' => 'id2', 'value' => 1],
          ['key' => 'id1', 'value' => 3],
        ],
      ],
    ]);






    /*
    $url = $query->getQueryUrl('MetaType', ['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'queryArgs' => [
        'orderBy' => 'amount',
        'order' => 'desc',
      ],
    ],[
      'metaType',
      'instances' => ['metaId', 'metas' => ['key', 'value']],
    ]);
    dd($url);
    */



	}




  /**
   * @param \Closure $newQuery
   * @param array $fields
   */
  public function testMetaInstanceQuery () {
    $this->beforeExecute();

    // Fields
    $instanceFields = [
      'nodes' => [
        'metaType',
        'metaId',
      ],
    ];
    $instancePaginatorFields = array_merge($instanceFields, [
      'paginator' => [
        'offset',
        'total'
      ],
    ]);
    $countFields = [
      'counts' => [
        'key',
        'value',
      ],
    ];

    // Closure to use new query every time
    $guzzle_client = $this->guzzle_client;
    $newQuery = static function () use ( $guzzle_client ) {
      return new QueryMetaInstance( $guzzle_client );
    };


    // status=ACTIVE | ALL
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => false,
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
        ],
      ],
    ], $instanceFields);
    $this->assertEquals($response->data(), [
      'nodes' => [
        ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
        ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
        ['metaType' => 'ProductEdit', 'metaId' => 'product1'],
      ],
    ]);
    // status=ACTIVE | LATEST
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
        ],
      ],
    ], $instanceFields);
    $this->assertEquals($response->data(), [
      'nodes' => [
        ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
        ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
      ],
    ]);




    // ORDER BY
    // -- desc
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'queryArgs' => [
        'orderBy' => 'amount',
        'order' => 'desc',
      ],
    ], $instanceFields);
    $this->assertEquals($response->data(), [
      'nodes' => [
        ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
        ['metaType' => 'ProductEdit', 'metaId' => 'product1'],
        ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
      ],
    ]);
    // -- asc
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'queryArgs' => [
        'orderBy' => 'amount',
        'order' => 'asc',
      ],
    ], $instanceFields);
    $this->assertEquals($response->data(), [
      'nodes' => [
        ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
        ['metaType' => 'ProductEdit', 'metaId' => 'product1'],
        ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
      ],
    ]);




    // -- AND WHERE
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
          'comparison' => '=',
        ],
        [
          'key' => 'objectId',
          'value' => 'id3',
          'comparison' => '=',
        ],
      ],
    ], $instanceFields);
    $this->assertEquals($response->data(), [
      'nodes' => [
        ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
      ],
    ]);


    // -- WHERE LIKE
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
          'comparison' => '=',
        ],
        [
          'key' => 'objectId',
          'value' => '2',
          'comparison' => '=~',
          'criterion' => 'OR',
        ],
        [
          'key' => 'objectId',
          'value' => '1',
          'comparison' => '=~',
          'criterion' => 'OR',
        ],
      ],
    ], $instanceFields);
    $this->assertEquals($response->data(), [
      'nodes' => [
        ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
      ],
    ]);



    // Pagination 1
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'queryArgs' => [
        'offset' => 1,
        'limit' => 1,
        'orderBy' => 'amount',
        'order' => 'desc',
      ],
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
          'comparison' => '=',
        ],
      ],
    ], $instancePaginatorFields);
    $this->assertEquals($response->data(), [
      'nodes' => [
        ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
      ],
      'paginator' => [
        'offset' => 1, 'total' => 2,
      ],
    ]);
    // Pagination 2
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'queryArgs' => [
        'offset' => 2,
        'limit' => 1,
        'orderBy' => 'amount',
        'order' => 'desc',
      ],
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
          'comparison' => '=',
        ],
      ],
    ], $instancePaginatorFields);
    $this->assertEquals($response->data(), [
      'nodes' => [
        ['metaType' => 'ProductEdit', 'metaId' => 'product3'],
      ],
      'paginator' => [
        'offset' => 2, 'total' => 2,
      ],
    ]);
    // Pagination 3
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'latestMetas' => true,
      'queryArgs' => [
        'offset' => 2,
        'limit' => 1,
        'orderBy' => 'amount',
        'order' => 'asc',
      ],
      'filter' => [
        [
          'key' => 'status',
          'value' => 'active',
          'comparison' => '=',
        ],
      ],
    ], $instancePaginatorFields);
    $this->assertEquals($response->data(), [
      'nodes' => [
        ['metaType' => 'ProductEdit', 'metaId' => 'product2'],
      ],
      'paginator' => [
        'offset' => 2, 'total' => 2,
      ],
    ]);



    // Count => EDIT
    $response = $newQuery()->execute(['metaType' => 'ProductEdit',
      'countBy' => '*',
    ], $countFields);
    $this->assertEquals($response->data(), [
      'counts' => [
        ['key' => '*', 'value' => 3]
      ],
    ]);
    // Count => Fave
    $response = $newQuery()->execute(['metaType' => 'ProductFave',
      'countBy' => '*',
    ], $countFields);
    $this->assertEquals($response->data(), [
      'counts' => [
        ['key' => '*', 'value' => 9]
      ],
    ]);


    // CountBy objectId with filter, order & pagination
    $response = $newQuery()->execute(['metaType' => 'ProductFave',
      'countBy' => 'objectId',
      'latestMetas' => true,
      'filter' => [
        [
          "key" => "status",
          "value" => "active"
        ],
        [
          "key" => "objectId",
          "value" => "id1",
          "comparison" => "=",
          "criterion" => "OR"
        ],
        [
          "key" => "objectId",
          "value" => "id2",
          "comparison" => "=",
          "criterion" => "OR"
        ],
      ],
    ], $countFields);
    $this->assertEquals($response->data(), [
      'counts' => [
        ['key' => 'id2', 'value' => 1],
        ['key' => 'id1', 'value' => 3],
      ],
    ]);


  }





	/**
	 * @param $data
	 * @return array
	 */
	protected function getLimitedResult (Response $response, $depth = 2) {
		$result = [];
		foreach ($response->data() as $meta_type) {
			$item = Arr::only($meta_type, ['metaType']);

			if ($depth >= 2) {
				$item['instances'] = [];
				foreach ($meta_type['instances'] as $instance) {
					$metas = array_get( $instance, 'metas' );
					$instance = Arr::only($instance, ['metaType', 'metaId']);

					if ( $metas && $depth >= 3 ) {
            $instance['metas'] = [];
            foreach ($metas as $meta) {
              $instance['metas'][] = Arr::only($meta, ['key', 'value']);
            }
          }

					$item['instances'][] = $instance;
				}
			}

			$result[] = $item;
		}
		return $result;
	}





}
