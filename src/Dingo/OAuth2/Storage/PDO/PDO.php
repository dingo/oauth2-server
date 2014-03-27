<?php namespace Dingo\OAuth2\Storage\PDO;

use PDO as Connection;

abstract class PDO {

	/**
	 * PDO connection instance.
	 * 
	 * @var \PDO
	 */
	protected $connection;

	/**
	 * Array of database table names.
	 * 
	 * @var array
	 */
	protected $tables;

	/**
	 * Create a new Dingo\OAuth2\Storage\PDO\PDO instance.
	 * 
	 * @param  \PDO  $connection
	 * @param  array  $tables
	 * @return void
	 */
	public function __construct(Connection $connection, array $tables)
	{
		$this->connection = $connection;
		$this->tables = $tables;
	}


}