<?php namespace Dingo\OAuth2\Grant;

use Dingo\OAuth2\ScopeValidator;
use Dingo\OAuth2\Storage\Adapter;
use Symfony\Component\HttpFoundation\Request;

abstract class Grant implements GrantInterface {

	protected $storage;

	protected $request;

	protected $scopeValidator;

	protected function validateConfidentialClient()
	{
		// Grab the redirection URI from the post data if there is one. This is
		// sent along when validating a client for some grant types. It doesn't
		// matter if we send along a "null" value though.
		$redirectUri = $this->request->request->get('redirect_uri');

		// If the "Authorization" header exists within the request then we will
		// attempt to pull the clients ID and secret from there.
		if ($this->request->headers->has('authorization'))
		{
			$id = $this->request->getUser();

			$secret = $this->request->getPassword();
		}

		// Otherwise we'll default to pulling the clients ID and secret from the
		// requests post data. It's preferred if clients use HTTP basic.
		else
		{
			$id = $this->request->request->get('client_id');

			$secret = $this->request->request->get('client_secret');
		}

		// If we have a client ID and secret we'll attempt to verify the client by
		// grabbing its details from the storage adapter.
		if (($id and $secret) and $client = $this->storage->get('client')->get($id, $secret, $redirectUri))
		{
			return $client;
		}

		throw new \Exception('invalid_client');
	}

	protected function validateScopes()
	{
		return $this->scopeValidator->validate();
	}

	public function setStorage(Adapter $storage)
	{
		$this->storage = $storage;

		return $this;
	}

	public function setRequest(Request $request)
	{
		$this->request = $request;

		return $this;
	}

	public function setScopeValidator(ScopeValidator $scopeValidator)
	{
		$this->scopeValidator = $scopeValidator;
	}

}