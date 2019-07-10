<?php
namespace WishKnish\KnishIO\Client;

use WishKnish\KnishIO\Client\Traits\Json;

/**
 * Class Meta
 * @package WishKnish\KnishIO\Client
 *
 * @property string $modelType
 * @property string $modelId
 * @property array $meta
 * @property $snapshotMolecule
 * @property integer $created_at
 *
 */
class Meta
{
    use Json;

    public $modelType;
    public $modelId;
    public $meta;
    public $snapshotMolecule;
    public $created_at;

    public function __construct ( $modelType, $modelId, $meta, $snapshotMolecule = null )
    {
        $this->modelType = $modelType;
        $this->modelId   = $modelId;
        $this->meta      = $meta;
        $this->snapshotMolecule = $snapshotMolecule;
        $this->created_at = time();
    }
}