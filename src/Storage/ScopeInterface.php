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
	 * @return \Dingo\OAuth2\Entity\Scope|bool
	 */
	public function get($scope);

	/**
	 * Insert a scope into storage.
	 * 
	 * Example MySQL query:
	 * 
	 * INSERT INTO oauth_scopes (scope, name, description) 
	 * VALUES (:scope, :name, :description)
	 * 
	 * @param  string  $scope
	 * @param  string  $name
	 * @param  string  $description
	 * @return \Dingo\OAuth2\Entity\Scope
	 */
	public function create($scope, $name, $description);

	/**
	 * Delete a scope from storage.
	 * 
	 * Example MySQL query:
	 * 
	 * DELETE FROM oauth_scopes WHERE oauth_scopes.scope = :scope
	 * 
	 * @param  string  $scope
	 * @return void
	 */
	public function delete($scope);

}