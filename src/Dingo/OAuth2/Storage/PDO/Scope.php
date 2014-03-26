<?php namespace Dingo\OAuth2\Storage\PDO;

use PDO;
use Dingo\OAuth2\Storage\ScopeInterface;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;

class Scope implements ScopeInterface {

	protected $connection;

	protected $tables;

	public function __construct(PDO $connection, array $tables)
	{
		$this->connection = $connection;
		$this->tables = $tables;
	}

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