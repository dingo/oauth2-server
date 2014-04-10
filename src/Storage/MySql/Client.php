<?php namespace Dingo\OAuth2\Storage\MySql;

use Dingo\OAuth2\Storage\ClientInterface;
use Dingo\OAuth2\Entity\Client as ClientEntity;

class Client extends MySql implements ClientInterface {

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
		if (isset($this->cache[$id]))
		{
			return $this->cache[$id];
		}

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

		// If only the clients redirection URI is given then we must correctly
		// validate the client by comparing its ID and the redirection URI.
		elseif (is_null($secret) and ! is_null($redirectUri))
		{
			$query = $this->connection->prepare(sprintf('SELECT %1$s.*, %2$s.uri AS redirect_uri
				FROM %1$s
				INNER JOIN %2$s ON %1$s.id = %2$s.client_id
				WHERE %1$s.id = :id
				AND %2$s.uri = :redirectUri', $this->tables['clients'], $this->tables['client_endpoints']));

			unset($bindings[':secret']);
		}

		// Lastly we'll validate the client by comparing just the ID.
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

		// If no redirection URI was given then we'll fetch one from storage so that
		// it can be included in the entity.
		if ( ! isset($client['redirect_uri']))
		{
			$query = $this->connection->prepare(sprintf('SELECT * FROM %1$s 
				WHERE %1$s.client_id = :client_id AND is_default = 1 LIMIT 1', $this->tables['client_endpoints']));

			if ($query->execute([':client_id' => $client['id']]) and $endpoint = $query->fetch())
			{
				$client['redirect_uri'] = $endpoint['uri'];
			}
			else
			{
				$client['redirect_uri'] = null;
			}
		}

		$client = new ClientEntity($client['id'], $client['secret'], $client['name'], (bool) $client['trusted'], $client['redirect_uri']);

		return $this->cache[$client->getId()] = $client;
	}

	/**
	 * Create a client and associated redirection URIs.
	 * 
	 * @param  string  $id
	 * @param  string  $secret
	 * @param  string  $name
	 * @param  array  $redirectUris
	 * @param  bool  $trusted
	 * @return \Dingo\OAuth2\Entity\Client|bool
	 */
	public function create($id, $secret, $name, array $redirectUris, $trusted = false)
	{
		$query = $this->connection->prepare(sprintf('INSERT INTO %1$s (id, secret, name, trusted) 
			VALUES (:id, :secret, :name, :trusted)', $this->tables['clients']));

		$bindings = [
			':id'      => $id,
			':secret'  => $secret,
			':name'    => $name,
			':trusted' => (int) $trusted
		];

		$query->execute($bindings);

		$redirectUri = null;

		$query = $this->connection->prepare(sprintf('INSERT INTO %1$s (client_id, uri, is_default) 
			VALUES (:client_id, :uri, :is_default)', $this->tables['client_endpoints']));

		foreach ($redirectUris as $uri)
		{
			// If this redirection URI is the default then we'll set our redirection URI
			// to this URI for when we return the client entity.
			if ($uri['default'])
			{
				$redirectUri = $uri['uri'];
			}

			$query->execute([
				':client_id' => $id,
				':uri' => $uri['uri'],
				':is_default' => (int) $uri['default']
			]);
		}

		return new ClientEntity($id, $secret, $name, (bool) $trusted, $redirectUri);
	}

	/**
	 * Delete a client and associated redirection URIs.
	 * 
	 * @param  string  $id
	 * @return void
	 */
	public function delete($id)
	{
		unset($this->cache[$id]);
		
		$query = $this->connection->prepare(sprintf('DELETE FROM %1$s WHERE %1$s.id = :id; 
			DELETE FROM %2$s WHERE %2$s.client_id = :id', $this->tables['clients'], $this->tables['client_endpoints']));

		$query->execute([':id' => $id]);
	}
	
}