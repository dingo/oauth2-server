<?php namespace Dingo\OAuth2\Storage;

use RuntimeException;

abstract class Adapter {

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
	abstract public function createAuthorizationCodeStorage();

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

}