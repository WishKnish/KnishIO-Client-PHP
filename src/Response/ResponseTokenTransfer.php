<?php
namespace WishKnish\KnishIO\Client\Response;

/**
 * Class ResponseTokenTransfer
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseTokenTransfer extends ResponseMolecule
{

    /**
     * @return array
     */
    public function payload ()
    {

        $result = new \ArrayObject( [ 'reason' => null, 'status' => null ], \ArrayObject::STD_PROP_LIST | \ArrayObject::ARRAY_AS_PROPS );
        [ $result->reason, $result->status ] = \array_unpacking(
            $this->data() ?: [
                'status' => 'rejected',
                'reason' => 'Invalid response from server',
            ],
            'reason',
            'status'
        );

        return $result->getArrayCopy();

    }

}
