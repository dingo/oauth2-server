<?php namespace Dingo\OAuth2\Storage;

use PDO;
use Dingo\OAuth2\Storage\MySql\Scope;
use Dingo\OAuth2\Storage\MySql\Token;
use Dingo\OAuth2\Storage\MySql\Client;
use Dingo\OAuth2\Storage\MySql\AuthorizationCode;

class MySqlAdapter extends Adapter {

	/**
	 * PDO connection.
	 * 
	 * @var \PDO
	 */
	protected $connection;

	/**
	 * Create a new Dingo\OAuth2\Storage\MySqlAdapter instance.
	 * 
	 * @param  \PDO  $connection
	 * @param  array  $tables
	 * @return void
	 */
	public function __construct(PDO $connection, array $tables = [])
	{
		$this->connection = $connection;
		$this->tables = array_merge($this->tables, $tables);
	}

	/**
	 * Create the client storage instance.
	 * 
	 * @return \Dingo\OAuth2\Storage\MySql\Client
	 */
	public function createClientStorage()
	{
		return new Client($this->connection, $this->tables);
	}
	
	/**
	 * Create the token storage instance.
	 * 
	 * @return \Dingo\OAuth2\Storage\MySql\Token
	 */
	public function createTokenStorage()
	{
		return new Token($this->connection, $this->tables);
	}

	/**
	 * Create the authorization code storage instance.
	 * 
	 * @return \Dingo\OAuth2\Storage\MySql\AuthorizationCode
	 */
	public function createAuthorizationStorage()
	{
		return new AuthorizationCode($this->connection, $this->tables);
	}

	/**
	 * Create the scope storage instance.
	 * 
	 * @return \Dingo\OAuth2\Storage\MySql\Scope
	 */
	public function createScopeStorage()
	{
		return new Scope($this->connection, $this->tables);
	}

}