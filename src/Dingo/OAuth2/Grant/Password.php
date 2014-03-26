<?php namespace Dingo\OAuth2\Grant;

use Closure;

class Password extends Grant {

	protected $authenticationCallback;

	public function execute()
	{
		$client = $this->validateConfidentialClient();

		$requestData = $this->request->request;

		if ( ! $username = $requestData->get('username') or ! $password = $requestData->get('password'))
		{
			throw new \Exception('invalid_request');
		}

		if ( ! $userId = call_user_func($this->authenticationCallback, $username, $password))
		{
			throw new \Exception('invalid_credentials');
		}

		$scopes = $this->validateScopes();

		// Generate and create a new access token. Once the token has been generated and
		// saved with the storage adapter we can return our array response.
		$expires = time() + $this->getTokenExpiration();

		if ( ! $token = $this->storage->get('token')->create($this->generateToken(), 'access', $client->getId(), $userId, $expires))
		{
			throw new \Exception('failed_to_save_token');
		}

		if ($scopes)
		{
			$this->storage->get('token')->associateScopes($token->getToken(), $scopes);
		}

		return $this->response($token);
	}

	public function setAuthenticationCallback(Closure $callback)
	{
		$this->authenticationCallback = $callback;
	}

	public function getGrantIdentifier()
	{
		return 'password';
	}

}