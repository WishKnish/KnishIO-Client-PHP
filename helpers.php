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
