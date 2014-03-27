<?php namespace Dingo\OAuth2\Entity;

class Client extends Entity {

	/**
	 * Create a new Dingo\OAuth2\Entity\Client instance.
	 * 
	 * @param  string  $id
	 * @param  string  $secret
	 * @param  string  $name
	 * @param  string  $redirectUri
	 * @return void
	 */
	public function __construct($id, $secret, $name, $redirectUri = null)
	{
		$this->id = $id;
		$this->secret = $secret;
		$this->name = $name;
		$this->redirectUri = $redirectUri;
	}

}