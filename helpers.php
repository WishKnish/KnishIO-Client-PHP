<?php

if ( ! \function_exists( 'default_if_null' ) ) {

	/**
	 * Get a default value if the passed value is null
	 * @param $value
	 * @param $default
	 * @return mixed
	 */
	function default_if_null ( $value, $default )
	{
		if ($value === null) {
			return $default;
		}
		return $value;
	}

}

if (! function_exists('array_has')) {
	/**
	 * Check if an item or items exist in an array using "dot" notation.
	 *
	 * @param  \ArrayAccess|array  $array
	 * @param  string|array  $keys
	 * @return bool
	 */
	function array_has($array, $keys)
	{
		$keys = (array) $keys;


		foreach ($keys as $key) {
			$_keys = explode('.', $key);
			$_array = $array;
			foreach ($_keys as $_key) {
				if (!array_key_exists($_key, $_array)) {
					return false;
				}
				$_array = $_array[$_key];
			}
		}
		return true;
	}
}

if (! function_exists('array_get')) {
	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * @param  \ArrayAccess|array  $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	function array_get($array, $keys, $default = null)
	{
		$keys = explode ('.', $keys);
		foreach ($keys as $key) {
			if (!array_has($array, $key) ) {
				return $default;
			}
			$array = $array[$key];
		}
		return $array;
	}
}
