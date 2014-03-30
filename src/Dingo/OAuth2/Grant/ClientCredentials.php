<?php namespace Dingo\OAuth2\Grant;

class ClientCredentials extends Grant {

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

		$scopes = $this->validateScopes();

		$token = $this->createToken('access', $client->getId(), null, $scopes);

		return $token;
	}

	/**
	 * Get the grant identifier.
	 * 
	 * @return string
	 */
	public function getGrantIdentifier()
	{
		return 'client_credentials';
	}

}