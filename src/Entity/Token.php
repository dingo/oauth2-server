<?php namespace Dingo\OAuth2\Entity;

class Token extends ScopeableEntity {

	/**
	 * Create a new Dingo\OAuth2\Entity\Token instance.
	 * 
	 * @param  string  $token
	 * @param  string  $type
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  int  $expires
	 * @return void
	 */
	public function __construct($token, $type, $clientId, $userId, $expires)
	{
		$this->token = $token;
		$this->type = $type;
		$this->clientId = $clientId;
		$this->userId = $userId;
		$this->expires = $expires;
		$this->scopes = [];
	}

}