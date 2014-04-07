<?php namespace Dingo\OAuth2\Storage\Redis;

use Dingo\OAuth2\Storage\ScopeInterface;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;

class Scope extends Redis implements ScopeInterface {

	/**
	 * Get a scope from storage.
	 * 
	 * @param  string  $scope
	 * @return \Dingo\OAuth2\Entity\Scope|false
	 */
	public function get($scope)
	{
		if ( ! $value = $this->getValue($scope, $this->tables['scopes']))
		{
			return false;
		}

		return new ScopeEntity($scope, $value['name'], $value['description']);
	}

}