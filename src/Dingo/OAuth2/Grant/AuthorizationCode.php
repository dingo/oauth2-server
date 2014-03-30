<?php namespace Dingo\OAuth2\Grant;

use Dingo\OAuth2\Exception\ClientException;

class AuthorizationCode extends ResponseGrant {

	/**
	 * Handle the authorization request by creating an authorization code.
	 * This code has a short expiration time of 10 minutes which
	 * cannot be changed.
	 * 
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  string  $redirectUri
	 * @param  array  $scopes
	 * @return \Dingo\OAuth2\Entity\AuthorizationCode
	 */
	public function handleAuthorizationRequest($clientId, $userId, $redirectUri, array $scopes)
	{
		$expires = time() + 600;

		$code = $this->storage->get('authorization')->create($this->generateToken(), $clientId, $userId, $redirectUri, $expires);

		$this->storage->get('authorization')->associateScopes($code->getCode(), $scopes);

		return $code;
	}

	/**
	 * Execute the grant flow.
	 * 
	 * @return array
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 * @throws \RuntimeException
	 */
	public function execute()
	{
		$this->validateRequestParameters(['redirect_uri', 'code']);

		// Retrieve the code from the storage and perform some checks to ensure that
		// the validated client above matches the client that the code was
		// issued to. We'll also ensure that the redirection URIs match
		// and that the code has not expired.
		if ( ! $code = $this->storage->get('authorization')->get($this->request->get('code')))
		{
			throw new ClientException('The authorization code does not exist.', 400);
		}

		$client = $this->validateConfidentialClient();

		if ($code->getClientId() != $client->getId())
		{
			throw new ClientException('The authorization code is not associated with the client.', 400);
		}

		if ($code->getRedirectUri() != $this->request->get('redirect_uri'))
		{
			throw new ClientException('The redirection URI does not match the redirection URI of the authorization code.', 400);
		}

		if ($code->getExpires() < time())
		{
			throw new ClientException('The authorization code has expired.', 400);
		}

		$token = $this->createToken('access', $client->getId(), $code->getUserId(), $code->getScopes());

		// We no longer need the authorization code so we can safely delete it
		// from the storage.
		$this->storage->get('authorization')->delete($code->getCode());

		return $token;
	}

	/**
	 * Get the response type.
	 * 
	 * @return string
	 */
	public function getResponseType()
	{
		return 'code';
	}

	/**
	 * Get the grant identifier.
	 * 
	 * @return string
	 */
	public function getGrantIdentifier()
	{
		return 'authorization_code';
	}

}