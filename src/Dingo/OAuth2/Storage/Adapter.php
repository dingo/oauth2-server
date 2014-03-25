<?php namespace Dingo\OAuth2\Storage;

use RuntimeException;

abstract class Adapter {

	protected $storages = [];

	abstract public function createClientStorage();

	abstract public function createAccessTokenStorage();
	
	abstract public function createRefreshTokenStorage();

	abstract public function createAuthorizationCodeStorage();

	abstract public function createSessionStorage();

	abstract public function createScopeStorage();

	public function get($storage)
	{
		if ( ! isset($this->storages[$storage]))
		{
			$this->storages[$storage] = $this->createStorage($storage);
		}

		return $this->storages[$storage];
	}

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