<?php


class PDOStub extends PDO {

	public function __construct() {}

}


class PasswordGrantStub extends Dingo\OAuth2\Grant\Grant {

	public function execute()
	{
		return new Dingo\OAuth2\Entity\Token('test', 'access', 'testclient', 1, strtotime(date('31/01/1991 12:00 PM')));
	}

	public function getGrantIdentifier()
	{
		return 'password';
	}

}


class AuthorizationCodeGrantStub extends Dingo\OAuth2\Grant\Grant {

	public function validateAuthorizationRequest()
	{
		return true;
	}

	public function handleAuthorizationRequest($clientId, $userId, $redirectUri, $scopes)
	{
		$code = new Dingo\OAuth2\Entity\AuthorizationCode('test', $clientId, $userId, $redirectUri, strtotime(date('31/01/1991 12:00 PM')));
		$code->attachScopes($scopes);

		return $code;
	}

	public function getResponseType()
	{
		return 'code';
	}

	public function getGrantIdentifier()
	{
		return 'authorization_code';
	}

}


class RefreshGrantStub extends Dingo\OAuth2\Grant\Grant {

	public function generateToken()
	{
		return 'test_refresh';
	}

	public function getGrantIdentifier()
	{
		return 'refresh_token';
	}

}


class AdapterStub extends Dingo\OAuth2\Storage\Adapter {

	public function createClientStorage()
	{
		return 'client';
	}

	public function createTokenStorage()
	{
		return 'token';
	}
	
	public function createAuthorizationStorage()
	{
		return 'authorization';
	}

	public function createScopeStorage()
	{
		return 'scope';
	}


}