<?php namespace Dingo\OAuth2\Grant;

use Dingo\OAuth2\Exception\ClientException;

class AuthorizationCode extends Grant {

	/**
	 * Validate an authorization request by checking for required parameters
	 * as well as validating the client and scopes.
	 * 
	 * @return array
	 */
	public function validateAuthorizationRequest()
	{
		$this->validateRequestParameters(['response_type', 'client_id', 'redirect_uri']);

		if ( ! $client = $this->storage->get('client')->get($this->request->get('client_id'), null, $this->request->get('redirect_uri', null)))
		{
			throw new ClientException('The client failed to authenticate.', 401);
		}

		$scopes = $this->validateScopes();

		$parameters = array_merge($this->request->query->all(), compact('scopes', 'client'));

		return $parameters;
	}

	/**
	 * Create an authorization code. This code has a short expiration time of
	 * 10 minutes which cannot be changed.
	 * 
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  string  $redirectUri
	 * @param  array  $scopes
	 * @return \Dingo\OAuth2\Entity\AuthorizationCode
	 */
	public function createAuthorizationCode($clientId, $userId, $redirectUri, array $scopes)
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
		$this->validateRequestParameters(['client_id', 'client_secret', 'redirect_uri', 'code']);

		$client = $this->validateConfidentialClient();

		// Retrieve the code from the storage and perform some checks to ensure that
		// the validated client above matches the client that the code was
		// issued to. We'll also ensure that the redirection URIs match
		// and that the code has not expired.
		$code = $this->storage->get('authorization')->get($this->request->get('code'));

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

		// Everything has been checked so we can proceed with the creation of the
		// access token. We'll grab the scopes from the authorization code
		// and associate them with the new token.
		$expires = time() + $this->accessTokenExpiration;

		$token = $this->storage->get('token')->create($this->generateToken(), 'access', $client->getId(), $code->getUserId(), $expires);

		if ($code->getScopes())
		{
			$this->storage->get('token')->associateScopes($token->getToken(), $code->getScopes());

			$token->attachScopes($code->getScopes());
		}

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