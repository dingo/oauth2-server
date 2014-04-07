<?php namespace Dingo\OAuth2\Storage\MySql;

use Dingo\OAuth2\Storage\TokenInterface;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;

class Token extends MySql implements TokenInterface {

	/**
	 * Insert a token into storage.
	 * 
	 * @param  string  $token
	 * @param  string  $type
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  int  $expires
	 * @return \Dingo\OAuth2\Entity\Token
	 */
	public function create($token, $type, $clientId, $userId, $expires)
	{
		$query = $this->connection->prepare(sprintf('INSERT INTO %1$s 
			(token, type, client_id, user_id, expires) 
			VALUES (:token, :type, :client_id, :user_id, :expires)', $this->tables['tokens']));

		$bindings = [
			':token'     => $token,
			':type'      => $type,
			':client_id' => $clientId,
			':user_id'   => $userId,
			':expires'   => date('Y-m-d H:i:s', $expires)
		];

		if ( ! $query->execute($bindings))
		{
			return false;
		}

		return new TokenEntity($token, $type, $clientId, $userId, $expires);
	}

	/**
	 * Associate scopes with a token.
	 * 
	 * @param  string  $token
	 * @param  array  $scopes
	 * @return void
	 */
	public function associateScopes($token, array $scopes)
	{
		$query = $this->connection->prepare(sprintf('INSERT INTO %1$s 
			(token, scope) VALUES 
			(:token, :scope)', $this->tables['token_scopes']));

		foreach ($scopes as $scope)
		{
			$query->execute([':token' => $token, ':scope' => $scope->getScope()]);
		}
	}

	/**
	 * Get an access token from storage.
	 * 
	 * @param  string  $token
	 * @return \Dingo\OAuth2\Entity\Token|bool
	 */
	public function get($token)
	{
		$query = $this->connection->prepare(sprintf('SELECT * FROM %1$s
			WHERE token = :token', $this->tables['tokens']));

		if ( ! $query->execute([':token' => $token]) or ! $token = $query->fetch())
		{
			return false;
		}

		$token = new TokenEntity($token['token'], $token['type'], $token['client_id'], $token['user_id'], strtotime($token['expires']));

		return $token;
	}

	/**
	 * Get an access token from storage with associated scopes.
	 * 
	 * @param  string  $token
	 * @return \Dingo\OAuth2\Entity\Token|bool
	 */
	public function getWithScopes($token)
	{
		if ( ! $token = $this->get($token))
		{
			return false;
		}

		// Now that the token has been fetched and the entity created we'll also fetch
		// the associated scopes of the token.
		$query = $this->connection->prepare(sprintf('SELECT %1$s.* FROM %1$s
			LEFT JOIN %2$s ON %1$s.scope = %2$s.scope
			WHERE %2$s.token = :token', $this->tables['scopes'], $this->tables['token_scopes']));

		if ($query->execute([':token' => $token->getToken()]))
		{
			$scopes = [];

			foreach ($query->fetchAll() as $scope)
			{
				$scopes[$scope['scope']] = new ScopeEntity($scope['scope'], $scope['name'], $scope['description']);
			}

			$token->attachScopes($scopes);
		}

		return $token;
	}

	/**
	 * Delete an access token from storage.
	 * 
	 * @param  string  $token
	 * @return void
	 */
	public function delete($token)
	{
		$query = $this->connection->prepare(sprintf('DELETE FROM %1$s WHERE token = :token;
			DELETE FROM %2$s WHERE token = :token', $this->tables['tokens'], $this->tables['token_scopes']));

		$query->execute([':token' => $token]);
	}

}