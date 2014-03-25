<?php namespace Dingo\OAuth2\Storage;

interface ClientInterface {

	public function get($id, $secret = null, $redirectUri = null);

}