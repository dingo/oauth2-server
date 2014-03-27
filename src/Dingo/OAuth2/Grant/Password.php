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
		$client = $this->validateConfidentialClient();

		$requestData = $this->request->request;

		if ( ! $username = $requestData->get('username') or ! $password = $requestData->get('password'))
		{
			throw new ClientException('The request is missing the "username" or "password" parameter.', 400);
		}

		if ( ! $userId = call_user_func($this->authenticationCallback, $username, $password))
		{
			throw new ClientException('The user credentials failed to authenticate.', 400);
		}

		$scopes = $this->validateScopes();

		// Generate and create a new access token. Once the token has been generated and
		// saved with the storage adapter we can return our array response.
		$expires = time() + $this->getTokenExpiration();

		$token = $this->storage->get('token')->create($this->generateToken(), 'access', $client->getId(), $userId, $expires);

		if ($scopes)
		{
			$this->storage->get('token')->associateScopes($token->getToken(), $scopes);
		}

		return $this->response($token);
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