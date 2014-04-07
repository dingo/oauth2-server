<?php namespace Dingo\OAuth2\Storage\Redis;

use Dingo\OAuth2\Storage\ClientInterface;
use Dingo\OAuth2\Entity\Client as ClientEntity;

class Client extends Redis implements ClientInterface {

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
		if ( ! $client = $this->getValue($id, $this->tables['clients']))
		{
			return false;
		}

		// Attempt to grab a redirection URI from the storage that matches the
		// supplied redirection URI. If we can't find a match then we'll set
		// this it as "null" for the time being.
		$client['redirect_uri'] = $this->getMatchingMember($id, $this->tables['client_endpoints'], function($endpoint) use ($redirectUri)
		{
			$endpoint = json_decode($endpoint, true);

			return $endpoint['uri'] == $redirectUri ? $endpoint['uri'] : null;
		});

		// If a secret and redirection URI were given then we must correctly
		// validate the client by comparing its ID, secret, and that
		// the supplied redirection URI was registered.
		if ( ! is_null($secret) and ! is_null($redirectUri))
		{
			if ($secret != $client['secret'] or $redirectUri != $client['redirect_uri'])
			{
				return false;
			}
		}

		// If only the clients secret is given then we must correctly validate
		// the client by comparing its ID and secret.
		elseif ( ! is_null($secret) and is_null($redirectUri))
		{
			if ($secret != $client['secret'])
			{
				return false;
			}
		}

		// If only the clients redirection URI is given then we must correctly
		// validate the client by comparing the redirection URI.
		elseif (is_null($secret) and ! is_null($redirectUri))
		{
			if ($redirectUri != $client['redirect_uri'])
			{
				return false;
			}
		}

		// If we don't have a redirection URI still and we've made it this far
		// then we'll give it one last shot to find the default redirection
		// URI for this client. Otherwise the redirection URI will be null.
		if ( ! $client['redirect_uri'])
		{
			$client['redirect_uri'] = $this->getMatchingMember($id, $this->tables['client_endpoints'], function($endpoint)
			{
				$endpoint = json_decode($endpoint, true);

				return $endpoint['is_default'] ? $endpoint['uri'] : null;
			});
		}

		return new ClientEntity($id, $client['secret'], $client['name'], $client['redirect_uri']);
	}

}