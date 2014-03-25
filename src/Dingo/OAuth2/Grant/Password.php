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

		if ( ! $id = call_user_func($this->authenticationCallback, $username, $password))
		{
			throw new \Exception('invalid_credentials');
		}

		$scopes = $this->validateScopes();

		return $client;
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