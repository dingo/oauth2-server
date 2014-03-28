<?php namespace Dingo\OAuth2\Server;

use Dingo\OAuth2\ScopeValidator;
use Dingo\OAuth2\Storage\Adapter;
use Dingo\OAuth2\Grant\GrantInterface;
use Dingo\OAuth2\Exception\ClientException;
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
	 * Array of registered grants.
	 * 
	 * @var array
	 */
	protected $grants = [];

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

		$this->grants[$key] = $grant;

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
	public function issueToken(array $payload = [])
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
			throw new ClientException('The authorization server does not support the requested grant.');
		}

		$grant = $this->grants[$grant];

		$token = $grant->setTokenExpiration($this->accessTokenExpiration)->execute();

		if (isset($this->grants['refresh']))
		{
			// TODO: Implement the addition of a refresh token.
		}

		return $token;
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

}