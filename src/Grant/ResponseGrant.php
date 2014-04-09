<?php namespace Dingo\OAuth2\Grant;

abstract class ResponseGrant extends Grant {

	/**
	 * Validate an authorization request by checking for required parameters
	 * as well as validating the client and scopes.
	 * 
	 * @return array
	 */
	public function validateAuthorizationRequest()
	{
		$this->validateRequestParameters(['response_type', 'client_id']);

		$client = $this->validateClient();

		$scopes = $this->validateScopes();

		$defaults = [
			'client_id'    => $this->request->get('client_id'),
			'user_id'      => $this->request->get('user_id'),
			'redirect_uri' => $this->request->get('redirect_uri'),
			'scopes'       => null,
			'client'       => null
		];

		$parameters = array_merge($defaults, compact('scopes', 'client'));

		return $parameters;
	}

	/**
	 * Handle the authorization request.
	 * 
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  string  $redirectUri
	 * @param  array  $scopes
	 * @return \Dingo\OAuth2\Entity\AuthorizationCode
	 */
	abstract public function handleAuthorizationRequest($clientId, $userId, $redirectUri, array $scopes);

}