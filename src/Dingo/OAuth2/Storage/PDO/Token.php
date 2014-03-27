<?php namespace Dingo\OAuth2\Storage\PDO;

use Dingo\OAuth2\Storage\TokenInterface;
use Dingo\OAuth2\Entity\Token as TokenEntity;

class Token implements TokenInterface {

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

	public function associateScopes($token, array $scopes)
	{
		foreach ($scopes as $scope)
		{
			$query = $this->connection->prepare(sprintf('INSERT INTO %1$s 
				(token, scope) VALUES 
				(:token, :scope)', $this->tables['token_scopes']));

			$query->execute([':token' => $token, ':scope' => $scope->getScope()]);
		}
	}

}