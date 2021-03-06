<?php
namespace WishKnish\KnishIO\Client\Response;


/**
 * Class ResponseAuthentication
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseAuthentication extends ResponseMolecule
{


	/**
	 * Payload key
	 *
	 * @param $key
	 * @return mixed
	 */
	private function payloadKey ($key) {
		if (!array_has($this->payload, $key) ) {
			throw new InvalidResponseException( 'ResponseAuthentication: \''.$key.'\' key is not found in the payload.' );
		}
		return array_get($this->payload, $key);
	}


	/**
	 * Token
	 */
	public function token () {
		return $this->payloadKey('token');
	}


	/**
	 * @return mixed
	 */
	public function time () {
		return $this->payloadKey('time');
	}

}
