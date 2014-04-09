<?php namespace Dingo\OAuth2\Storage\MySql;

use Dingo\OAuth2\Storage\ScopeInterface;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;

class Scope extends MySql implements ScopeInterface {

	/**
	 * Get a scope from storage.
	 * 
	 * @param  string  $scope
	 * @return \Dingo\OAuth2\Entity\Scope|false
	 */
	public function get($scope)
	{
		if (isset($this->cache[$scope]))
		{
			return $this->cache[$scope];
		}

		$query = $this->connection->prepare(sprintf('SELECT * FROM %1$s WHERE %1$s.scope = :scope', $this->tables['scopes']));

		if ( ! $query->execute([':scope' => $scope]) or ! $scope = $query->fetch())
		{
			return false;
		}

		return $this->cache[$scope['scope']] = new ScopeEntity($scope['scope'], $scope['name'], $scope['description']);
	}

	/**
	 * Insert a scope into storage.
	 * 
	 * @param  string  $scope
	 * @param  string  $name
	 * @param  string  $description
	 * @return \Dingo\OAuth2\Entity\Scope|bool
	 */
	public function create($scope, $name, $description)
	{
		$query = $this->connection->prepare(sprintf('INSERT INTO %1$s 
			(scope, name, description) 
			VALUES (:scope, :name, :description)', $this->tables['scopes']));

		$bindings = [
			':scope'       => $scope,
			':name'        => $name,
			':description' => $description,
		];

		$query->execute($bindings);

		return new ScopeEntity($scope, $name, $description);
	}

	/**
	 * Delete a scope from storage.
	 * 
	 * @param  string  $scope
	 * @return void
	 */
	public function delete($scope)
	{
		unset($this->cache[$scope]);
		
		$query = $this->connection->prepare(sprintf('DELETE FROM %1$s WHERE scope = :scope', $this->tables['scopes']));

		$query->execute([':scope' => $scope]);
	}

}