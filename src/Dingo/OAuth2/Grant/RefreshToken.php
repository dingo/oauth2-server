<?php namespace Dingo\OAuth2\Grant;

class RefreshToken extends Grant {

	/**
	 * Execute the grant flow.
	 * 
	 * @return array
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 * @throws \RuntimeException
	 */
	public function execute()
	{
		list ($token) = $this->validateRequestParameters(['refresh_token']);

		$client = $this->validateConfidentialClient();

		$oldToken = $this->storage->get('token')->getWithScopes($token);

		$scopes = $this->validateScopes($oldToken->getScopes());

		// Create a new access token in the storage and associate the scopes with
		// this new token.
		$accessToken = $this->createToken('access', $oldToken->getClientId(), $oldToken->getUserId(), $scopes);
		
		// Delete the old refresh token from the storage so that we can create a
		// new refresh token. Again the scopes will be associated.
		$this->storage->get('token')->delete($oldToken->getToken());

		$refreshToken = $this->createToken('refresh', $oldToken->getClientId(), $oldToken->getUserId(), $scopes);

		return $accessToken;
	}

	/**
	 * Get the grant identifier.
	 * 
	 * @return string
	 */
	public function getGrantIdentifier()
	{
		return 'refresh_token';
	}

}