<?php namespace Dingo\OAuth2\Storage;

use RuntimeException;

abstract class Adapter {

	/**
	 * Array of tables used when interacting with database.
	 * 
	 * @var array
	 */
	protected $tables = [
		'clients'                   => 'oauth_clients',
		'client_endpoints'          => 'oauth_client_endpoints',
		'tokens'                    => 'oauth_tokens',
		'token_scopes'              => 'oauth_token_scopes',
		'authorization_codes'       => 'oauth_authorization_codes',
		'authorization_code_scopes' => 'oauth_authorization_code_scopes',
		'scopes'                    => 'oauth_scopes'
	];

	/**
	 * Array of storage instances for adapter.
	 * 
	 * @var array
	 */
	protected $storages = [];

	/**
	 * Create the client storage instance.
	 * 
	 * @return \Dingo\OAuth2\Storage\ClientInterface
	 */
	abstract public function createClientStorage();

	/**
	 * Create the token storage instance.
	 * 
	 * @return \Dingo\OAuth2\Storage\TokenInterface
	 */
	abstract public function createTokenStorage();
	
	/**
	 * Create the authorization code storage instance.
	 * 
	 * @return \Dingo\OAuth2\Storage\AuthorizationCodeInterface
	 */
	abstract public function createAuthorizationStorage();

	/**
	 * Create the scope storage instance.
	 * 
	 * @return \Dingo\OAuth2\Storage\ScopeInterface
	 */
	abstract public function createScopeStorage();

	/**
	 * Get a storage instance from the adapter.
	 * 
	 * @param  string  $storage
	 * @return mixed
	 */
	public function get($storage)
	{
		if ( ! isset($this->storages[$storage]))
		{
			$this->storages[$storage] = $this->createStorage($storage);
		}

		return $this->storages[$storage];
	}

	/**
	 * Create a storage instance for the adapter.
	 * 
	 * @param  string  $storage
	 * @return mixed
	 * @throws \RuntimeException
	 */
	protected function createStorage($storage)
	{
		$method = 'create'.ucfirst($storage).'Storage';

		if (method_exists($this, $method))
		{
			return $this->{$method}();
		}

		throw new RuntimeException("Storage driver [{$storage}] is not supported.");
	}

	/**
	 * Set the tables to be used by storages.
	 * 
	 * @param  array  $tables
	 * @return \Dingo\OAuth2\Storage\Adapter
	 */
	public function setTables(array $tables)
	{
		$this->tables = array_merge($this->tables, $tables);

		return $this;
	}

}