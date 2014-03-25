<?php namespace Dingo\OAuth2;

class Token {

	public static function make($length = 40)
	{
		$randomBytes = openssl_random_pseudo_bytes($length * 2);

		if ( ! $randomBytes)
		{
			throw new RuntimeException('Failed to make a token.');
		}

		return substr(str_replace(['+', '=', '/'], '', base64_encode($randomBytes)), 0, $length);
	}

}