<?php namespace Dingo\OAuth2\Storage\PDO;

use Dingo\OAuth2\Storage\ClientInterface;
use Dingo\OAuth2\Entity\Client as ClientEntity;

class Client extends PDO implements ClientInterface {

	/**
	 * Get a client from storage.
	 * 
	 * @param  string  $id
	 * @param  string  $secret
	 * @param  string  $redirectUri
	 * @return \Dingo\OAuth2\Entity\Client|false
	 */
	public function get($id, $secret = null, $redirectUri = null)
	{
		// Prepare the default bindings that will be used for a fully constructed
		// PDO statement.
		$bindings = [
			':id'          => $id,
			':secret'      => $secret,
			':redirectUri' => $redirectUri
		];

		// If a secret and redirection URI were given then we must correctly
		// validate the client by comparing its ID, secret, and that
		// the supplied redirection URI was registered.
		if ( ! is_null($secret) and ! is_null($redirectUri))
		{
			$query = $this->connection->prepare(sprintf('SELECT %1$s.*, %2$s.uri AS redirect_uri
				FROM %1$s
				INNER JOIN %2$s ON %1$s.id = %2$s.client_id
				WHERE %1$s.id = :id
				AND %1$s.secret = :secret
				AND %2$s.uri = :redirectUri', $this->tables['clients'], $this->tables['client_endpoints']));
		}

		// If only the clients secret is given then we must correctly validate
		// the client by comparing its ID and secret.
		elseif ( ! is_null($secret) and is_null($redirectUri))
		{
			$query = $this->connection->prepare(sprintf('SELECT * FROM %1$s
				WHERE %1$s.id = :id
				AND %1$s.secret = :secret', $this->tables['clients']));

			unset($bindings[':redirectUri']);
		}

		// Lastly we'll validate the client by comparing its ID.
		else
		{
			$query = $this->connection->prepare(sprintf('SELECT * FROM %1$s
				WHERE %1$s.id = :id', $this->tables['clients']));

			unset($bindings[':secret'], $bindings[':redirectUri']);
		}

		if ( ! $query->execute($bindings) or ! $client = $query->fetch())
		{
			return false;
		}

		// If no redirection URI was given then we'll set it to null so that we
		// can create a new Dingo\OAuth2\Entity\Client instance.
		if ( ! isset($client['redirect_uri']))
		{
			$client['redirect_uri'] = null;
		}

		return new ClientEntity($client['id'], $client['secret'], $client['name'], $client['redirect_uri']);
	}
	
}