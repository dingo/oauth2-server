<?php namespace Dingo\OAuth2\Storage\Redis;

use Dingo\OAuth2\Entity\Scope as ScopeEntity;
use Dingo\OAuth2\Storage\AuthorizationCodeInterface;
use Dingo\OAuth2\Entity\AuthorizationCode as AuthorizationCodeEntity;

class AuthorizationCode extends Redis implements AuthorizationCodeInterface {

	/**
	 * Insert an authorization code into storage.
	 * 
	 * @param  string  $code
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  string  $redirectUri
	 * @param  int  $expires
	 * @return \Dingo\OAuth2\Entity\AuthorizationCode
	 */
	public function create($code, $clientId, $userId, $redirectUri, $expires)
	{
		$payload = [
			'client_id'    => $clientId,
			'user_id'      => $userId,
			'redirect_uri' => $redirectUri,
			'expires'      => $expires
		];

		if ( ! $this->setValue($code, $this->tables['authorization_codes'], $payload))
		{
			return false;
		}

		return new AuthorizationCodeEntity($code, $clientId, $userId, $redirectUri, $expires);
	}

	/**
	 * Associate scopes with an authorization code.
	 * 
	 * @param  string  $code
	 * @param  array  $scopes
	 * @return void
	 */
	public function associateScopes($code, array $scopes)
	{
		foreach ($scopes as $scope)
		{
			$this->pushList($code, $this->tables['authorization_code_scopes'], [
				'scope'       => $scope->getScope(),
				'name'        => $scope->getName(),
				'description' => $scope->getDescription()
			]);
		}
	}

	/**
	 * Get a code from storage.
	 * 
	 * @param  string  $code
	 * @return \Dingo\OAuth2\Entity\AuthorizationCode
	 */
	public function get($code)
	{
		if ( ! $value = $this->getValue($code, $this->tables['authorization_codes']))
		{
			return false;
		}

		$code = new AuthorizationCodeEntity($code, $value['client_id'], $value['user_id'], $value['redirect_uri'], $value['expires']);

		$scopes = [];

		foreach ($this->getList($code->getCode(), $this->tables['authorization_code_scopes']) as $scope)
		{
			$scopes[$scope['scope']] = new ScopeEntity($scope['scope'], $scope['name'], $scope['description']);
		}

		$code->attachScopes($scopes);

		return $code;
	}

	/**
	 * Delete an authorization code from storage.
	 * 
	 * @param  string  $code
	 * @return void
	 */
	public function delete($code)
	{
		$this->deleteKey($code, $this->tables['authorization_codes']);

		$this->deleteKey($code, $this->tables['authorization_code_scopes']);
	}

}