<?php namespace Dingo\OAuth2\Server;

use Closure;
use RuntimeException;
use Dingo\OAuth2\Entity\Entity;
use Dingo\OAuth2\ScopeValidator;
use Dingo\OAuth2\Storage\Adapter;
use Dingo\OAuth2\Grant\GrantInterface;
use Dingo\OAuth2\Exception\ClientException;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Symfony\Component\HttpFoundation\Request;
use Dingo\OAuth2\Entity\AuthorizationCode as AuthorizationCodeEntity;

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
			$this->responseTypes[$grant->getResponseType()] = $key;
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
			throw new ClientException('unsupported_request_method', 'The request method must be POST.', 400);
		}

		if ( ! $grant = $this->request->request->get('grant_type'))
		{
			throw new ClientException('missing_parameter', 'The request is missing the "grant_type" parameter.', 400);
		}

		if ( ! isset($this->grants[$grant]))
		{
			throw new ClientException('unknown_grant', 'The authorization server does not support the requested grant.', 400);
		}

		$accessToken = $this->grants[$grant]->execute();

		$response = $this->makeResponseFromEntity($accessToken);

		// If the "refresh" grant has been registered we'll issue a refresh token
		// so that clients can easily renew their access tokens.
		if (isset($this->grants['refresh_token']) and in_array($grant, $this->refreshEnabledGrants))
		{
			$refreshToken = $this->issueRefreshToken($accessToken);

			$response['refresh_token'] = $refreshToken->getToken();
		}

		// When making a response from an entity we may get some optional
		// parameters. We'll simply unset these if they exist.
		foreach (['scope', 'state'] as $optional)
		{
			if (isset($response[$optional])) unset($response[$optional]);
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

		$refreshToken = $this->storage('token')->create($refreshToken, 'refresh', $accessToken->getClientId(), $accessToken->getUserId(), $expires);

		$this->storage('token')->associateScopes($refreshToken->getToken(), $accessToken->getScopes());

		return $refreshToken;
	}

	/**
	 * Make an array response from an entity.
	 * 
	 * @param  \Dingo\OAuth2\Entity\Entity  $entity
	 * @return array
	 */
	protected function makeResponseFromEntity(Entity $entity)
	{
		if ($entity instanceof TokenEntity)
		{
			$response = [
				'access_token' => $entity->getToken(),
				'token_type'   => 'Bearer',
				'expires'      => $entity->getExpires(),
				'expires_in'   => $this->accessTokenExpiration
			];
		}
		elseif ($entity instanceof AuthorizationCodeEntity)
		{
			$response = ['code' => $entity->getCode()];
		}

		// If the request had a state parameter then we'll return the exact
		// same state parameter so the client can validate it.
		if ($this->request->get('state'))
		{
			$response['state'] = $this->request->get('state');
		}

		// If the entity has any scopes then we'll build a scope string from
		// keys of the scopes using the scope delimiter that should've been
		// used in the initial request.
		if ($scopes = $entity->getScopes())
		{
			$response['scope'] = implode($this->getScopeValidator()->getScopeDelimiter(), array_keys($scopes));
		}

		return $response;
	}

	/**
	 * Validate an authorization request.
	 * 
	 * @return array
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 */
	public function validateAuthorizationRequest()
	{
		if ( ! isset($this->responseTypes[$this->request->get('response_type')]))
		{
			throw new ClientException('unknown_response_type', 'The authorization server does not recognize the provided response type.', 400);
		}

		$key = $this->responseTypes[$this->request->get('response_type')];

		return $this->grants[$key]->validateAuthorizationRequest();
	}

	/**
	 * Handle an authorization request. Depending on the response type
	 * this will either use the Authorization Code Grant or the
	 * Implicit Grant.
	 * 
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  string  $redirectUri
	 * @param  array  $scopes
	 * @return array
	 */
	public function handleAuthorizationRequest($clientId, $userId, $redirectUri, array $scopes)
	{
		$key = $this->responseTypes[$this->request->get('response_type')];

		$code = $this->grants[$key]->handleAuthorizationRequest($clientId, $userId, $redirectUri, $scopes);

		return $this->makeResponseFromEntity($code);
	}

	/**
	 * Make a redirection URI from a response array created by the
	 * makeResponseFromEntity method.
	 * 
	 * @param  array  $response
	 * @return string
	 */
	public function makeRedirectUri(array $response)
	{
		$separator = $this->request->get('response_type') == 'code' ? '?' : '#';

		if ( ! $redirectUri = $this->request->get('redirect_uri'))
		{
			$client = $this->storage('client')->get($this->request->get('client_id'));
			
			if ( ! $redirectUri = $client->getRedirectUri())
			{
				throw new RuntimeException('Client does not have any associated redirection URI.');
			}
		}

		return $redirectUri.$separator.http_build_query($response);
	}

	/**
	 * Get a specific storage from the storage adapter.
	 * 
	 * @param  string  $storage
	 * @return mixed
	 */
	public function storage($storage)
	{
		return $this->getStorage($storage);
	}

	/**
	 * Get the storage adapter instance or a specific storage instance.
	 * 
	 * @return \Dingo\OAuth2\Storage\Adapter
	 */
	public function getStorage($storage = null)
	{
		return ! is_null($storage) ? $this->storage->get($storage) : $this->storage;
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
			$this->scopeValidator = new ScopeValidator($this->request, $this->storage('scope'));
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