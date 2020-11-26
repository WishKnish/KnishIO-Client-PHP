<?php
namespace WishKnish\KnishIO\Client\Response;


use WishKnish\KnishIO\Client\Exception\InvalidResponseException;

/**
 * Class ResponseAuthorization
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseAuthorization extends ResponseMolecule
{


	/**
	 * Payload key
	 *
	 * @param $key
	 * @return mixed
	 */
	private function payloadKey ($key) {
		if (!array_has($this->payload, $key) ) {
			throw new InvalidResponseException( 'ResponseAuthorization: \''.$key.'\' key is not found in the payload.' );
		}
		return array_get($this->payload, $key);
	}


	/**
	 * Token
	 */
	public function token () {
		return $this->payloadKey('token' );
	}


	/**
	 * @return mixed
	 */
	public function time () {
		return $this->payloadKey( 'time' );
	}

}
