<?php namespace Dingo\OAuth2\Entity;

class Token extends Entity {

	/**
	 * Create a new Dingo\OAuth2\Entity\Token instance.
	 * 
	 * @param  string  $token
	 * @param  string  $type
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  int  $expires
	 * @return void
	 */
	public function __construct($token, $type, $clientId, $userId, $expires)
	{
		$this->token = $token;
		$this->type = $type;
		$this->clientId = $clientId;
		$this->userId = $userId;
		$this->expires = $expires;
		$this->scopes = [];
	}

	/**
	 * Attach scopes to the token.
	 * 
	 * @param  array  $scopes
	 * @return void
	 */
	public function attachScopes(array $scopes)
	{
		$this->scopes = $scopes;
	}

	/**
	 * Get a scope.
	 * 
	 * @param  string  $scope
	 * @return \Dingo\OAuth2\Entity\Scope
	 */
	public function getScope($scope)
	{
		return $this->scopes[$scope];
	}

	/**
	 * Determine if token has a scope.
	 * 
	 * @param  string  $scope
	 * @return bool
	 */
	public function hasScope($scope)
	{
		return isset($this->scopes[$scope]);
	}

}