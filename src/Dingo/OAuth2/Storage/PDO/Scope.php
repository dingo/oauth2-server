<?php namespace Dingo\OAuth2\Storage\PDO;

use Dingo\OAuth2\Storage\ScopeInterface;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;

class Scope extends PDO implements ScopeInterface {

	/**
	 * Get a scope from storage.
	 * 
	 * @param  string  $scope
	 * @return \Dingo\OAuth2\Entity\Scope|false
	 */
	public function get($scope)
	{
		$query = $this->connection->prepare(sprintf('SELECT * FROM %1$s WHERE %1$s.scope = :scope', $this->tables['scopes']));

		if ( ! $query->execute([':scope' => $scope]) or ! $scope = $query->fetch())
		{
			return false;
		}

		return new ScopeEntity($scope['scope'], $scope['name'], $scope['description']);
	}

}