<?php namespace Dingo\OAuth2\Entity;

abstract class ScopeableEntity extends Entity {

	/**
	 * Attach scopes to the token.
	 * 
	 * @param  array  $scopes
	 * @return \Dingo\OAuth2\Entity\ScopeableEntity
	 */
	public function attachScopes(array $scopes)
	{
		$this->scopes = $scopes;

		return $this;
	}

	/**
	 * Get a scope.
	 * 
	 * @param  string  $scope
	 * @return \Dingo\OAuth2\Entity\Scope
	 */
	public function getScope($scope)
	{
		return $this->scopes[$scope];
	}

	/**
	 * Determine if token has a scope.
	 * 
	 * @param  string  $scope
	 * @return bool
	 */
	public function hasScope($scope)
	{
		return isset($this->scopes[$scope]);
	}
	
}