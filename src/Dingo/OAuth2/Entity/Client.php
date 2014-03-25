<?php namespace Dingo\OAuth2\Entity;

class Client extends Entity {

	public function __construct($id, $secret, $name, $redirectUri = null)
	{
		$this->id = $id;
		$this->secret = $secret;
		$this->name = $name;
		$this->redirectUri = $redirectUri;
	}

}