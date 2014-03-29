<?php namespace Dingo\OAuth2;

use Dingo\OAuth2\Storage\ScopeInterface;
use Dingo\OAuth2\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;

class ScopeValidator {

	/**
	 * Symfony request instance.
	 * 
	 * @var \Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * Scope delimiter.
	 * 
	 * @var string
	 */
	protected $scopeDelimiter = ' ';

	/**
	 * Default scope if no scope was provided.
	 * 
	 * @var array|string
	 */
	protected $defaultScope;

	/**
	 * Indicates if a scope is required.
	 * 
	 * @var bool
	 */
	protected $scopeRequired = false;

	/**
	 * Create a new Dingo\OAuth2\ScopeValidator instance.
	 * 
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  \Dingo\OAuth2\Storage\ScopeInterface  $storage
	 * @return void
	 */
	public function __construct(Request $request, ScopeInterface $storage)
	{
		$this->request = $request;
		$this->storage = $storage;
	}

	/**
	 * Validate the requested scopes. If an array of original scopes is given
	 * then it will also validate that any scopes provided exist in the
	 * original scopes (from a refresh token).
	 * 
	 * @param  array  $originalScopes
	 * @return array
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 */
	public function validate(array $originalScopes = [])
	{
		$requestedScopes = explode($this->scopeDelimiter, $this->request->get('scope'));

		// Spin through all the scopes in the request and filter out any that
		// are blank or invalid.
		$requestedScopes = array_filter(array_map(function($scope)
		{ 
			return trim($scope);
		}, $requestedScopes));

		if ($this->scopeRequired and is_null($this->defaultScope) and empty($requestedScopes) and empty($originalScopes))
		{
			throw new ClientException('The request is missing the "scope" parameter.', 400);
		}
		elseif ($this->defaultScope and empty($requestedScopes))
		{
			$requestedScopes = (array) $this->defaultScope;
		}
		elseif ( ! empty($originalScopes) and empty($requestedScopes))
		{
			$requestedScopes = array_keys($originalScopes);
		}

		// If original scopes were declared for this token we'll compare the requested
		// scopes to ensure that any new scopes aren't added. If a new scope is
		// found we'll abort with an exception.
		if ( ! empty($originalScopes))
		{
			foreach ($requestedScopes as $requestedScope)
			{
				if ( ! isset($originalScopes[$requestedScope]))
				{
					throw new ClientException("The requested scope [{$requestedScope}] was not originally requested for this token.", 400);
				}
			}
		}

		$scopes = [];

		foreach ($requestedScopes as $requestedScope)
		{
			if ( ! $scope = $this->storage->get($requestedScope))
			{
				throw new ClientException("The requested scope [{$requestedScope}] is invalid or unknown.", 400);
			}

			$scopes[$scope->getScope()] = $scope;
		}

		return $scopes;
	}

	/**
	 * Set the scope delimiter.
	 * 
	 * @param  string  $scopeDelimiter
	 * @return \Dingo\OAuth2\ScopeValidator
	 */
	public function setScopeDelimiter($scopeDelimiter)
	{
		$this->scopeDelimiter = $scopeDelimiter;

		return $this;
	}

	/**
	 * Set the default scope.
	 * 
	 * @param  string|array  $defaultScope
	 * @return \Dingo\OAuth2\ScopeValidator
	 */
	public function setDefaultScope($defaultScope)
	{
		$this->defaultScope = $defaultScope;

		return $this;
	}

	/**
	 * Set the vaildator to require a scope.
	 * 
	 * @return \Dingo\OAuth2\ScopeValidator
	 */
	public function requireScope()
	{
		$this->scopeRequired = true;

		return $this;
	}

}