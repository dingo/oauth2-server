<?php namespace Dingo\OAuth2\Storage\MySql;

use PDO;

abstract class MySql {

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
	 * Cached queries and the results.
	 * 
	 * @var array
	 */
	protected $cache = [];

	/**
	 * Create a new Dingo\OAuth2\Storage\MySql\MySql instance.
	 * 
	 * @param  \PDO  $connection
	 * @param  array  $tables
	 * @return void
	 */
	public function __construct(PDO $connection, array $tables)
	{
		$this->connection = $connection;
		$this->tables = $tables;
	}


}