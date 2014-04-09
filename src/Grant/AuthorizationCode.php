<?php namespace Dingo\OAuth2\Grant;

use Closure;
use Dingo\OAuth2\Exception\ClientException;

class AuthorizationCode extends ResponseGrant {

	/**
	 * Authorized callback used once access token is issued.
	 * 
	 * @var \Closure
	 */
	protected $authorizedCallback;

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

		$code = $this->storage('authorization')->create($this->generateToken(), $clientId, $userId, $redirectUri, $expires);

		$this->storage('authorization')->associateScopes($code->getCode(), $scopes);

		$code->attachScopes($scopes);

		// If we have an authorized callback set by the developer we'll fire it
		// now. This is handy when developers want to avoid prompting a user
		// to authorize a client that they've authorized in the past.
		if ($this->authorizedCallback instanceof Closure)
		{
			// Before we fire the authorized callback we'll pull the client from
			// storage again so we can hand that off to the callback along
			// with the code entity.
			$client = $this->storage('client')->get($clientId);

			call_user_func($this->authorizedCallback, $code, $client);
		}

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
		$this->validateRequestParameters(['code']);

		// Retrieve the code from the storage and perform some checks to ensure that
		// the validated client above matches the client that the code was
		// issued to. We'll also ensure that the redirection URIs match
		// and that the code has not expired.
		if ( ! $code = $this->storage('authorization')->get($this->request->get('code')))
		{
			throw new ClientException('unknown_authorization_code', 'The authorization code does not exist.', 400);
		}

		$client = $this->strictlyValidateClient();

		if ($code->getClientId() != $client->getId())
		{
			throw new ClientException('mismatched_client', 'The authorization code is not associated with the client.', 400);
		}

		if ($code->getRedirectUri())
		{
			$redirectUri = $this->request->get('redirect_uri');

			if ( ! $redirectUri or $redirectUri != $code->getRedirectUri())
			{
				throw new ClientException('mismatched_redirection_uri', 'The redirection URI does not match the redirection URI of the authorization code.', 400);
			}
		}

		if ($code->getExpires() < time())
		{
			throw new ClientException('expired_authorization_code', 'The authorization code has expired.', 400);
		}

		$token = $this->createToken('access', $client->getId(), $code->getUserId(), $code->getScopes());

		// We no longer need the authorization code so we can safely delete it
		// from the storage.
		$this->storage('authorization')->delete($code->getCode());

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

	/**
	 * Set the authorized callback.
	 * 
	 * @param  \Closure  $callback
	 * @return \Dingo\OAuth2\Server\Authorization
	 */
	public function setAuthorizedCallback(Closure $callback)
	{
		$this->authorizedCallback = $callback;

		return $this;
	}

}