<?php namespace Dingo\OAuth2\Storage\PDO;

use Dingo\OAuth2\Entity\Scope as ScopeEntity;
use Dingo\OAuth2\Storage\AuthorizationCodeInterface;
use Dingo\OAuth2\Entity\AuthorizationCode as AuthorizationCodeEntity;

class AuthorizationCode extends PDO implements AuthorizationCodeInterface {

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
		$query = $this->connection->prepare(sprintf('INSERT INTO %1$s 
			(code, client_id, user_id, redirect_uri, expires) 
			VALUES (:code, :client_id, :user_id, :redirect_uri, :expires)', $this->tables['authorization_codes']));

		$bindings = [
			':code'         => $code,
			':client_id'    => $clientId,
			':user_id'      => $userId,
			':redirect_uri' => $redirectUri,
			':expires'      => date('Y-m-d H:i:s', $expires)
		];

		if ( ! $query->execute($bindings))
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
		$query = $this->connection->prepare(sprintf('INSERT INTO %1$s 
			(code, scope) VALUES 
			(:code, :scope)', $this->tables['authorization_code_scopes']));

		foreach ($scopes as $scope)
		{
			$query->execute([':code' => $code, ':scope' => $scope->getScope()]);
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
		$query = $this->connection->prepare(sprintf('SELECT * FROM %1$s
			WHERE code = :code', $this->tables['authorization_codes']));

		if ( ! $query->execute([':code' => $code]) or ! $code = $query->fetch())
		{
			return false;
		}

		$code = new AuthorizationCodeEntity($code['code'], $code['client_id'], $code['user_id'], $code['redirect_uri'], strtotime($code['expires']));

		// Now that the code has been fetched and the entity created we'll also fetch
		// the associated scopes of the code.
		$query = $this->connection->prepare(sprintf('SELECT %1$s.* FROM %1$s
			LEFT JOIN %2$s ON %1$s.scope = %2$s.scope
			WHERE %2$s.code = :code', $this->tables['scopes'], $this->tables['authorization_code_scopes']));

		if ($query->execute([':code' => $code->getCode()]))
		{
			$scopes = [];

			foreach ($query->fetchAll() as $scope)
			{
				$scopes[$scope['scope']] = new ScopeEntity($scope['scope'], $scope['name'], $scope['description']);
			}

			$code->attachScopes($scopes);
		}

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
		$query = $this->connection->prepare(sprintf('DELETE FROM %1$s WHERE code = :code;
			DELETE FROM %2$s WHERE code = :code', $this->tables['authorization_codes'], $this->tables['authorization_code_scopes']));

		$query->execute([':code' => $code]);
	}

}