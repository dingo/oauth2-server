<?php namespace Dingo\OAuth2\Storage;

interface ScopeInterface {

	/**
	 * Get a scope from storage. Should return false if scope was not found.
	 * 
	 * Example MySQL query:
	 * 
	 * SELECT * FROM oauth_scopes WHERE oauth_scopes.scope = :scope
	 * 
	 * @param  string  $scope
	 * @return \Dingo\OAuth2\Entity\Scope|false
	 */
	public function get($scope);

}