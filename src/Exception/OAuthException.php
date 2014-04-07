<?php namespace Dingo\OAuth2\Exception;

use Exception;
use RuntimeException;

class OAuthException extends RuntimeException {

	/**
	 * HTTP status code.
	 * 
	 * @var int
	 */
	protected $statusCode;

	/**
	 * Create a new Dingo\OAuth2\Exception\OAuthException instance.
	 * 
	 * @param  string  $message
	 * @param  int  $statusCode
	 * @param  int  $code
	 * @param  \Exception  $previous
	 * @return void
	 */
	public function __construct($message, $statusCode, $code = 0, Exception $previous = null)
	{
		parent::__construct($message, $code, $previous);

		$this->statusCode = $statusCode;
	}

	/**
	 * Get the HTTP status code.
	 * 
	 * @return int
	 */
	public function getStatusCode()
	{
		return $this->statusCode;
	}

}