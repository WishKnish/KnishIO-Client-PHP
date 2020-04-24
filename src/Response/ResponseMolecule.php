<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Molecule;

/**
 * Class ResponseMolecule
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseMolecule extends Response
{
	protected $dataKey = 'data.ProposeMolecule';

	protected $payload;


	/**
	 *
	 */
	public function init () {

		// Get a json payload
		$payload_json = array_get($this->data(), 'payload');

		// Decode payload
		$this->payload = \json_decode( $payload_json, true );
	}


	/**
	 * @return Molecule|null
	 */
    public function molecule () {
    	if ( !$data = $this->data() ) {
    		return null;
		}

    	$molecule = new Molecule();
		$molecule->molecularHash = array_get($data, 'molecularHash');
		$molecule->status = array_get($data, 'status');
		$molecule->createdAt = array_get($data, 'createdAt');
        return $molecule;
    }


	/**
	 * Success?
	 *
	 * @return mixed
	 */
	public function success () {
		return ($this->status() === 'accepted');
	}


	/**
	 * @return mixed
	 */
    public function status () {
    	return array_get($this->data(), 'status', 'rejected');
	}


	/**
	 * @return mixed
	 */
	public function reason () {
		return array_get($this->data(), 'reason', 'Invalid response from server');
	}


	/**
	 * @return mixed|null
	 */
	public function payload () {
		return $this->payload;
	}


}
