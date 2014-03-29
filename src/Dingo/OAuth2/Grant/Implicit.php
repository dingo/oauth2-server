<?php namespace Dingo\OAuth2\Grant;

class Implicit extends Grant {

	/**
	 * Execute the grant flow.
	 * 
	 * @return array
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 * @throws \RuntimeException
	 */
	public function execute()
	{
		$this->validateRequestParameters(['client_id', 'redirect_uri']);

		$scopes = $this->validateScopes();

		$token = $this->createToken('access', $client->getId(), $code->getUserId(), $code->getScopes());

		return $token;
	}

	/**
	 * Get the response type.
	 * 
	 * @return string
	 */
	public function getResponseType()
	{
		return 'token';
	}

	/**
	 * Get the grant identifier.
	 * 
	 * @return string
	 */
	public function getGrantIdentifier()
	{
		return 'implicit';
	}

}