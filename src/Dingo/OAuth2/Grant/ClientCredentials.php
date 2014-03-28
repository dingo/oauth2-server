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

		// Generate and create a new access token. Once the token has been generated and
		// saved with the storage adapter we can return our array response.
		$expires = time() + $this->accessTokenExpiration;

		$token = $this->storage->get('token')->create($this->generateToken(), 'access', $client->getId(), null, $expires);

		if ($scopes)
		{
			$this->storage->get('token')->associateScopes($token->getToken(), $scopes);

			$token->attachScopes($scopes);
		}

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