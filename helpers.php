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


if ( ! \function_exists( 'env' ) ) {

	/**
	 * Get a default value if the passed value is null
	 * @param $value
	 * @param $default
	 * @return mixed
	 */
	function env ( $key )
	{
		return isset($key) ? $key : null;
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
	function array_has($array, $key)
	{
		return isset($array[$key]);
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
	function array_get($array, $key, $default = null)
	{
		return array_has($array, $key) ? $array[$key] : $default;
	}
}
