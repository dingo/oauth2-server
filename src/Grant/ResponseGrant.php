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
		$this->validateRequestParameters(['response_type', 'client_id', 'redirect_uri']);

		$client = $this->validatePublicClient();

		$scopes = $this->validateScopes();

		$parameters = array_merge($this->request->query->all(), compact('scopes', 'client'));

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