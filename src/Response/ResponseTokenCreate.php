<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Response;

/**
 * Class ResponsTokenCreate
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseTokenCreate extends ResponseMolecule
{

    /**
     * @return array
     */
    public function payload ()
    {

        return $this->data();

    }

}
