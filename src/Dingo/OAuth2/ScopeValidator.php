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
	 * Validate the requested scopes.
	 * 
	 * @return array
	 * @throws \Dingo\OAuth2\Exception\ClientException
	 */
	public function validate()
	{
		$requestedScopes = explode($this->scopeDelimiter, $this->request->request->get('scope'));

		// Spin through all the scopes in the request and filter out any that
		// are blank or invalid.
		$requestedScopes = array_filter(array_map(function($scope)
		{ 
			return trim($scope);
		}, $requestedScopes));

		if ($this->scopeRequired and is_null($this->defaultScope) and empty($requestedScopes))
		{
			throw new ClientException('The request is missing the "scope" parameter.', 400);
		}
		elseif ($this->defaultScope and empty($requestedScopes))
		{
			$requestedScopes = (array) $this->defaultScope;
		}

		$scopes = [];

		foreach ($requestedScopes as $requestedScope)
		{
			if ( ! $scope = $this->storage->get($requestedScope))
			{
				throw new ClientException("The requested scope [{$requestedScope}] is invalid or unknown.");
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