<?php namespace Dingo\OAuth2\Storage;

interface TokenInterface {

	public function create($token, $type, $clientId, $userId, $expires);

}