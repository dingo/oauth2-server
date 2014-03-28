<?php namespace Dingo\OAuth2\Entity;

class AuthorizationCode extends Entity {

	/**
	 * Create a new Dingo\OAuth2\Entity\AuthorizationCode instance.
	 * 
	 * @param  string  $code
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  string  $redirectUri
	 * @param  int  $expires
	 * @return void
	 */
	public function __construct($code, $clientId, $userId, $redirectUri, $expires)
	{
		$this->code = $code;
		$this->clientId = $clientId;
		$this->userId = $userId;
		$this->redirectUri = $redirectUri;
		$this->expires = $expires;
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