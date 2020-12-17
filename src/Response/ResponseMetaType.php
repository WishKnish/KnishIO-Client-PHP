<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Response;


/**
 * Class ResponseMetaType
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseMetaType extends Response
{
	protected $dataKey = 'data.MetaType';

  /**
   * @return |null
   */
	public function payload () {
    $data = $this->data();

    if ( !$data ) {
      return null;
    }

    return $data[ 0 ][ 'instances' ];
	}

}
