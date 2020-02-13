<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.
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
