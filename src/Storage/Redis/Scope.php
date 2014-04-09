<?php namespace Dingo\OAuth2\Storage\Redis;

use Dingo\OAuth2\Storage\ScopeInterface;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;

class Scope extends Redis implements ScopeInterface {

	/**
	 * Get a scope from storage.
	 * 
	 * @param  string  $scope
	 * @return \Dingo\OAuth2\Entity\Scope|bool
	 */
	public function get($scope)
	{
		if ( ! $value = $this->getValue($scope, $this->tables['scopes']))
		{
			return false;
		}

		return new ScopeEntity($scope, $value['name'], $value['description']);
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
		$payload = [
			'name' => $name,
			'description' => $description
		];

		$this->setValue($scope, $this->tables['scopes'], $payload);

		// Push the scope onto the scopes set so that we can easily manage all
		// scopes with Redis.
		$this->pushSet(null, $this->tables['scopes'], $scope);

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
		$this->deleteKey($scope, $this->tables['scopes']);

		$this->deleteSet(null, $this->tables['scopes'], $scope);
	}

}