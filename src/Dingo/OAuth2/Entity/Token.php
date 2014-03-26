<?php namespace Dingo\OAuth2\Entity;

class Token extends Entity {

	public function __construct($token, $type, $clientId, $userId, $expires)
	{
		$this->token = $token;
		$this->type = $type;
		$this->clientId = $clientId;
		$this->userId = $userId;
		$this->expires = $expires;
	}

}