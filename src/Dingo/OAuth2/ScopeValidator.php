<?php namespace Dingo\OAuth2;

use Dingo\OAuth2\Storage\ScopeInterface;
use Symfony\Component\HttpFoundation\Request;

class ScopeValidator {

	protected $request;

	protected $scopeDelimiter = ' ';

	protected $defaultScope;

	protected $scopeRequired = false;

	public function __construct(Request $request, ScopeInterface $storage)
	{
		$this->request = $request;
		$this->storage = $storage;
	}

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
			throw new \Exception('invalid_request scope is required');
		}
		elseif ($this->defaultScope and empty($requestedScopes))
		{
			$requestedScopes = (array) $this->defaultScope;
		}

		$scopes = [];

		foreach ($requestedScopes as $scope)
		{
			if ( ! $scope = $this->storage->get($scope))
			{
				throw new \Exception('invalid_scope');
			}

			$scopes[$scope->getId()] = $scope;
		}

		return $scopes;
	}

	public function setScopeDelimiter($scopeDelimiter)
	{
		$this->scopeDelimiter = $scopeDelimiter;

		return $this;
	}

	public function setDefaultScope($defaultScope)
	{
		$this->defaultScope = $defaultScope;

		return $this;
	}

	public function requireScope()
	{
		$this->scopeRequired = true;

		return $this;
	}

}