<?php namespace Dingo\OAuth2\Entity;

class AuthorizationCode extends ScopeableEntity {

	/**
	 * Create a new Dingo\OAuth2\Entity\AuthorizationCode instance.
	 * 
	 * @param  string  $code
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  string  $redirectUri
	 * @param  int  $expires
	 * @return void
	 */
	public function __construct($code, $clientId, $userId, $redirectUri, $expires)
	{
		$this->code = $code;
		$this->clientId = $clientId;
		$this->userId = $userId;
		$this->redirectUri = $redirectUri;
		$this->expires = $expires;
		$this->scopes = [];
	}

}