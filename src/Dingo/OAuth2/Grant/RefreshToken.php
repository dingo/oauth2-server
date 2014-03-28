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
		if ( ! $token = $this->request->request->get('refresh_token'))
		{
			throw new ClientException('The request is missing the "refresh_token" parameter.', 400);
		}

		$client = $this->validateConfidentialClient();

		$oldToken = $this->storage->get('token')->get($token);

		$scopes = $this->validateScopes($oldToken->getScopes());

		// Create a new access token in the storage and associate the scopes with
		// this new token.
		$accessToken = $this->createToken('access', $oldToken, $scopes);
		
		// Delete the old refresh token from the storage so that we can create a
		// new refresh token. Again the scopes will be associated.
		$this->storage->get('token')->delete($oldToken->getToken());

		$refreshToken = $this->createToken('refresh', $oldToken, $scopes);

		return $accessToken;
	}

	/**
	 * Create a new token of a given type.
	 * 
	 * @param  string  $type
	 * @param  \Dingo\OAuth2\Entity\Token  $oldToken
	 * @param  array  $scopes
	 * @return \Dingo\OAuth2\Entity\Token
	 */
	protected function createToken($type, $oldToken, array $scopes)
	{
		$expires = time() + $this->accessTokenExpiration;

		$token = $this->storage->get('token')->create($this->generateToken(), $type, $oldToken->getClientId(), $oldToken->getUserId(), $expires);

		$this->storage->get('token')->associateScopes($token->getToken(), $scopes);

		$token->attachScopes($scopes);

		return $token;
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