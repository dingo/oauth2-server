<?php namespace Dingo\OAuth2\Grant;

use Dingo\OAuth2\Token;
use Dingo\OAuth2\ScopeValidator;
use Dingo\OAuth2\Storage\Adapter;
use Dingo\OAuth2\Exception\ClientException;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Symfony\Component\HttpFoundation\Request;

abstract class Grant implements GrantInterface {

	/**
	 * Storage adapter instance.
	 * 
	 * @var \Dingo\OAuth2\Storage\Adapter
	 */
	protected $storage;

	/**
	 * Symfony request instance.
	 * 
	 * @var \Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * Scope validator instance.
	 * 
	 * @var \Dingo\OAuth2\ScopeValidator
	 */
	protected $scopeValidator;

	/**
	 * Access token expiration in seconds.
	 * 
	 * @var int
	 */
	protected $accessTokenExpiration;

	/**
	 * Refresh token expiration in seconds.
	 * 
	 * @var int
	 */
	protected $refreshTokenExpiration;

	/**
	 * Validate a client. If strictly validating an ID and secret are required.
	 * 
	 * @param  bool  $strict
	 * @return \Dingo\OAuth2\Entity\Client
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 */ 
	protected function validateClient($strict = false)
	{
		// Grab the redirection URI from the post data if there is one. This is
		// sent along when validating a client for some grant types. It doesn't
		// matter if we send along a "null" value though.
		$redirectUri = $this->request->get('redirect_uri');
		
		$id = $this->request->getUser() ?: $this->request->get('client_id');

		$secret = $this->request->getPassword() ?: $this->request->get('client_secret');

		// If we have a client ID and secret we'll attempt to verify the client by
		// grabbing its details from the storage adapter.
		if (( ! $strict or ($strict and $id and $secret)) and $client = $this->storage->get('client')->get($id, $secret, $redirectUri))
		{
			return $client;
		}

		throw new ClientException('client_authentication_failed', 'The client failed to authenticate.', 401);
	}

	/**
	 * Strictly validate a client.
	 * 
	 * @param  bool  $strict
	 * @return \Dingo\OAuth2\Entity\Client
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 */
	protected function strictlyValidateClient()
	{
		return $this->validateClient(true);
	}

	/**
	 * Validate the requested scopes.
	 * 
	 * @param  array  $originalScopes
	 * @return array
	 */
	protected function validateScopes(array $originalScopes = [])
	{
		return $this->scopeValidator->validate($originalScopes);
	}

	/**
	 * Validate that the request includes given parameters.
	 * 
	 * @param  array  $parameters
	 * @return array
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 */
	protected function validateRequestParameters(array $parameters)
	{
		$values = [];

		foreach ($parameters as $parameter)
		{
			if ( ! $this->request->get($parameter))
			{
				throw new ClientException('missing_parameter', 'The request is missing the "'.$parameter.'" parameter.', 400);
			}

			$values[] = $this->request->get($parameter);
		}

		return $values;
	}

	/**
	 * Create a new token in the storage.
	 * 
	 * @param  string  $type
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  array  $scopes
	 * @return \Dingo\OAuth2\Entity\Token
	 */
	protected function createToken($type, $clientId, $userId, array $scopes = [])
	{
		$token = $this->generateToken();

		$expires = time() + $this->{$type.'TokenExpiration'};

		$token = $this->storage->get('token')->create($token, $type, $clientId, $userId, $expires);

		if ($scopes)
		{
			$this->storage->get('token')->associateScopes($token->getToken(), $scopes);

			$token->attachScopes($scopes);
		}

		return $token;
	}

	/**
	 * Generate a new token.
	 * 
	 * @return string
	 */
	public function generateToken()
	{
		return Token::make();
	}

	/**
	 * Set the storage adapter instance.
	 * 
	 * @param  \Dingo\OAuth2\Storage\Adapter  $storage
	 * @return \Dingo\OAuth2\Grant\Grant
	 */
	public function setStorage(Adapter $storage)
	{
		$this->storage = $storage;

		return $this;
	}

	/**
	 * Set the symfony request instance.
	 * 
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return \Dingo\OAuth2\Grant\Grant
	 */
	public function setRequest(Request $request)
	{
		$this->request = $request;

		return $this;
	}

	/**
	 * Set the scope validator instance.
	 * 
	 * @param  \Dingo\OAuth2\ScopeValidator  $scopeValidator
	 * @return \Dingo\OAuth2\Grant\Grant
	 */
	public function setScopeValidator(ScopeValidator $scopeValidator)
	{
		$this->scopeValidator = $scopeValidator;

		return $this;
	}

	/**
	 * Set the access token expiration time in seconds.
	 * 
	 * @param  int  $expires
	 * @return \Dingo\OAuth2\Grant\Grant
	 */
	public function setAccessTokenExpiration($expires)
	{
		$this->accessTokenExpiration = $expires;

		return $this;
	}

	/**
	 * Set the refresh token expiration time in seconds.
	 * 
	 * @param  int  $expires
	 * @return \Dingo\OAuth2\Grant\Grant
	 */
	public function setRefreshTokenExpiration($expires)
	{
		$this->refreshTokenExpiration = $expires;

		return $this;
	}

	/**
	 * Get the access token expiration time in seconds.
	 * 
	 * @return int
	 */
	public function getAccessTokenExpiration()
	{
		return $this->accessTokenExpiration;
	}

	/**
	 * Get the refresh token expiration time in seconds.
	 * 
	 * @return int
	 */
	public function getRefreshTokenExpiration()
	{
		return $this->accessTokenExpiration;
	}

	/**
	 * Get the response type.
	 * 
	 * @return string
	 */
	public function getResponseType()
	{
		return null;
	}

}