<?php namespace Dingo\OAuth2\Server;

use Dingo\OAuth2\ScopeValidator;
use Dingo\OAuth2\Storage\Adapter;
use Dingo\OAuth2\Grant\GrantInterface;
use Dingo\OAuth2\Exception\ClientException;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Symfony\Component\HttpFoundation\Request;

class Authorization {

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
	 * Array of registered grants.
	 * 
	 * @var array
	 */
	protected $grants = [];

	/**
	 * Access token expiration in seconds.
	 * 
	 * @var int
	 */
	protected $accessTokenExpiration = 3600;

	/**
	 * Refresh token expiration in seconds.
	 * 
	 * @var int
	 */
	protected $refreshTokenExpiration = 3600;

	/**
	 * Array of grants that are refresh token enabled.
	 * 
	 * @var array
	 */
	protected $refreshEnabledGrants = [
		'password',
		'authorization_code'
	];

	/**
	 * Array of valid response types.
	 * 
	 * @var array
	 */
	protected $responseTypes = [];

	/**
	 * Create a new Dingo\OAuth2\Server\Authorization instance.
	 * 
	 * @param  \Dingo\OAuth2\Storage\Adapter  $storage
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function __construct(Adapter $storage, Request $request = null)
	{
		$this->storage = $storage;
		$this->request = $request ?: Request::createFromGlobals();
	}

	/**
	 * Register a grant with the server so that tokens can be issued
	 * with that particular grant.
	 * 
	 * @param  \Dingo\OAuth2\Grant\GrantInterface  $grant
	 * @return \Dingo\OAuth2\Server\Authorization
	 */
	public function registerGrant(GrantInterface $grant)
	{
		$key = $grant->getGrantIdentifier();

		// Get an instance of the scope validator and set it on the grant so
		// that scopes can be validated when authorizing a request.
		$scopeValidator = $this->getScopeValidator();

		$grant->setScopeValidator($scopeValidator);

		// Grants will often need to interact with the request instance and
		// the storage adapter so we'll set these on the grant.
		$grant->setRequest($this->request) and $grant->setStorage($this->storage);

		// Set the access token expiration time and the refresh token
		// expiration time on each of the grants. Some use both and
		// some don't but all grants can have both.
		$grant->setAccessTokenExpiration($this->accessTokenExpiration) and $grant->setRefreshTokenExpiration($this->refreshTokenExpiration);

		$this->grants[$key] = $grant;

		if ($grant->getResponseType())
		{
			$this->responseTypes[] = $grant->getResponseType();
		}

		return $this;
	}

	/**
	 * Issue an access token and (if requested and applicable) a refresh token.
	 * Returns an array containing the access token, the type of token, the
	 * expiration time and the number of seconds until the token expires.
	 * If applicable and enabled, a refresh token will also be included.
	 * 
	 * @return array
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 */
	public function issueAccessToken(array $payload = [])
	{
		// The payload may optionally be given as a parameter to this method
		// instead of in the request body. This is useful when proxying
		// AJAX requests to avoid disclosing confidential client
		// details in your source code.
		if ( ! empty($payload))
		{
			$this->request->request->replace($payload);
		}

		if ( ! $this->request->isMethod('post'))
		{
			throw new ClientException('The request method must be POST.', 400);
		}

		if ( ! $grant = $this->request->request->get('grant_type'))
		{
			throw new ClientException('The request is missing the "grant_type" parameter.', 400);
		}

		if ( ! isset($this->grants[$grant]))
		{
			throw new ClientException('The authorization server does not support the requested grant.', 400);
		}

		$accessToken = $this->grants[$grant]->execute();

		$response = $this->makeResponseFromToken($accessToken);

		// If the "refresh" grant has been registered we'll issue a refresh token
		// so that clients can easily renew their access tokens.
		if (isset($this->grants['refresh_token']) and in_array($grant, $this->refreshEnabledGrants))
		{
			$refreshToken = $this->issueRefreshToken($accessToken);

			$response['refresh_token'] = $refreshToken;
		}

		return $response;
	}

	/**
	 * Issue a refresh token.
	 * 
	 * @param  \Dingo\OAuth2\Entity\Token  $accessToken
	 * @return string
	 */
	protected function issueRefreshToken(TokenEntity $accessToken)
	{
		$refreshToken = $this->grants['refresh_token']->generateToken();

		$expires = time() + $this->refreshTokenExpiration;

		$this->storage->get('token')->create($refreshToken, 'refresh', $accessToken->getClientId(), $accessToken->getUserId(), $expires);

		$this->storage->get('token')->associateScopes($refreshToken, $accessToken->getScopes());

		return $refreshToken;
	}

	/**
	 * Make an array response from an access token.
	 * 
	 * @param  \Dingo\OAuth2\Entity\Token  $accessToken
	 * @return array
	 */
	protected function makeResponseFromToken(TokenEntity $accessToken)
	{
		return [
			'access_token' => $accessToken->getToken(),
			'token_type'   => 'Bearer',
			'expires'      => $accessToken->getExpires(),
			'expires_in'   => $this->accessTokenExpiration
		];
	}

	/**
	 * Validate an authorization request. This performs a few prior checks
	 * before handing the heavy lifitng off to the authorization code
	 * grant.
	 * 
	 * @return array
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 */
	public function validateAuthorizationRequest()
	{
		if ( ! isset($this->grants['authorization_code']))
		{
			throw new ClientException('The authorization code grant is not registered with the authorization server.', 400);
		}

		if ( ! in_array($this->request->get('response_type'), $this->responseTypes))
		{
			throw new ClientException('The authorization server does not recognize the provided response type.', 400);
		}

		return $this->grants['authorization_code']->validateAuthorizationRequest();
	}

	/**
	 * Create an authorization code.
	 * 
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  string  $redirectUri
	 * @param  array  $scopes
	 * @return \Dingo\OAuth2\Entity\AuthorizationCode
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 */
	public function createAuthorizationCode($clientId, $userId, $redirectUri, array $scopes)
	{
		if ( ! isset($this->grants['authorization_code']))
		{
			throw new ClientException('The authorization server does not support the requested grant.', 400);
		}

		return $this->grants['authorization_code']->createAuthorizationCode($clientId, $userId, $redirectUri, $scopes);
	}

	/**
	 * Get the storage adapter instance.
	 * 
	 * @return \Dingo\OAuth2\Storage\Adapter
	 */
	public function getStorage()
	{
		return $this->storage;
	}

	/**
	 * Get the symfony request instance.
	 * 
	 * @return \Symfony\Component\HttpFoundation\Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * Get the scope validator instance. If not defined a new instance
	 * will be instantiated.
	 * 
	 * @return \Dingo\OAuth2\ScopeValidator
	 */
	public function getScopeValidator()
	{
		if ( ! isset($this->scopeValidator))
		{
			$this->scopeValidator = new ScopeValidator($this->request, $this->storage->get('scope'));
		}

		return $this->scopeValidator;
	}

	/**
	 * Set the scope validator instance.
	 * 
	 * @param  \Dingo\OAuth2\ScopeValidator
	 * @return \Dingo\OAuth2\Server\Authorization
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
	 * @return \Dingo\OAuth2\Server\Authorization
	 */
	public function setAccessTokenExpiration($expires = 3600)
	{
		$this->accessTokenExpiration = $expires;

		return $this;
	}

	/**
	 * Set the refresh token expiration time in seconds.
	 * 
	 * @param  int  $expires
	 * @return \Dingo\OAuth2\Server\Authorization
	 */
	public function setRefreshTokenExpiration($expires = 3600)
	{
		$this->refreshTokenExpiration = $expires;

		return $this;
	}

	/**
	 * Determine if authorization server has a grant.
	 * 
	 * @param  string  $grant
	 * @return bool
	 */
	public function hasGrant($grant)
	{
		return isset($this->grants[$grant]);
	}

	/**
	 * Get a grant from the authorization server.
	 * 
	 * @param  string  $grant
	 * @return \Dingo\OAuth2\Grant\Grant
	 */
	public function getGrant($grant)
	{
		return $this->grants[$grant];
	}

}