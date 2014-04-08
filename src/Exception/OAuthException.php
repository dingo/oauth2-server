<?php namespace Dingo\OAuth2\Exception;

use Exception;
use RuntimeException;

class OAuthException extends RuntimeException {

	/**
	 * Generic error type.
	 * 
	 * @var string
	 */
	protected $error;

	/**
	 * HTTP status code.
	 * 
	 * @var int
	 */
	protected $statusCode;

	/**
	 * Create a new Dingo\OAuth2\Exception\OAuthException instance.
	 * 
	 * @param  string  $error
	 * @param  string  $message
	 * @param  int  $statusCode
	 * @param  int  $code
	 * @param  \Exception  $previous
	 * @return void
	 */
	public function __construct($error, $message, $statusCode, $code = 0, Exception $previous = null)
	{
		$this->error = $error;
		$this->statusCode = $statusCode;
		
		parent::__construct($message, $code, $previous);
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

	/**
	 * Get the generic error type.
	 * 
	 * @return int
	 */
	public function getError()
	{
		return $this->error;
	}

}