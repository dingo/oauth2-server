<?php namespace Dingo\OAuth2\Grant;

class Implicit extends ResponseGrant {

	/**
	 * Handle the authorization request by creating an access token.
	 * 
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  string  $redirectUri
	 * @param  array  $scopes
	 * @return \Dingo\OAuth2\Entity\Token
	 */
	public function handleAuthorizationRequest($clientId, $userId, $redirectUri, array $scopes)
	{
		$token = $this->createToken('access', $clientId, $userId, $scopes);

		return $token;
	}

	/**
	 * Execute the grant flow.
	 * 
	 * @return void
	 */
	public function execute()
	{
		// The implicit grant flow is never executed because the access token is
		// returned to the client when handling the authorization request. This
		// is because the implicit grant essentially "skips" the generation of
		// an authorization code. Refer to section 4.2 of RFC 6749.
	}

	/**
	 * Get the response type.
	 * 
	 * @return string
	 */
	public function getResponseType()
	{
		return 'token';
	}

	/**
	 * Get the grant identifier.
	 * 
	 * @return string
	 */
	public function getGrantIdentifier()
	{
		return 'implicit';
	}

}