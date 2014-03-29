<?php namespace Dingo\OAuth2\Grant;

use Closure;
use RuntimeException;
use Dingo\OAuth2\Exception\ClientException;

class Password extends Grant {

	/**
	 * The authentication callback used to authenticate a resource owner (user).
	 * 
	 * @var \Closure
	 */
	protected $authenticationCallback;

	/**
	 * Execute the grant flow.
	 * 
	 * @return array
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 * @throws \RuntimeException
	 */
	public function execute()
	{
		$requestData = $this->request->request;

		list ($username, $password) = $this->validateRequestParameters(['username', 'password']);

		if ( ! $userId = call_user_func($this->authenticationCallback, $username, $password))
		{
			throw new ClientException('The user credentials failed to authenticate.', 400);
		}

		$client = $this->validateConfidentialClient();

		$scopes = $this->validateScopes();

		$token = $this->createToken('access', $client->getId(), $userId, $scopes);

		return $token;
	}

	/**
	 * Set the authentication callback used to authenticate a resource owner (user).
	 * 
	 * @param  \Closure  $callback
	 * @return \Dingo\OAuth2\Grant\Password
	 */
	public function setAuthenticationCallback(Closure $callback)
	{
		$this->authenticationCallback = $callback;

		return $this;
	}

	/**
	 * Get the grant identifier.
	 * 
	 * @return string
	 */
	public function getGrantIdentifier()
	{
		return 'password';
	}

}