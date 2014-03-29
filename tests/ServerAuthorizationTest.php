<?php

use Mockery as m;
use Dingo\OAuth2\Server\Authorization;
use Symfony\Component\HttpFoundation\Request;
use Dingo\OAuth2\Entity\Token as TokenEntity;

class ServerAuthorizationTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testRegisteringGrantWithAuthorizationServer()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage);

		$authorization->registerGrant(new PasswordGrantStub);

		$this->assertTrue($authorization->hasGrant('password'));
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 */
	public function testIssuingAccessTokenFailsWhenRequestIsNotPost()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage, Request::create('testing', 'GET'));

		$authorization->issueAccessToken();
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 */
	public function testIssuingAccessTokenFailsWhenNoGrantTypeParameter()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage, Request::create('testing', 'POST'));

		$authorization->issueAccessToken();
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 */
	public function testIssuingAccessTokenFailsWhenGrantTypeIsUnknown()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage, Request::create('testing', 'POST', ['grant_type' => 'testing']));

		$authorization->issueAccessToken();
	}


	public function testIssuingAccessTokenSucceeds()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage, Request::create('testing', 'POST', ['grant_type' => 'password']));

		$authorization->registerGrant(new PasswordGrantStub);

		$token = $authorization->issueAccessToken();

		$this->assertEquals('test', $token['access_token']);
	}


	public function testIssuingAccessTokenFromParametersSucceeds()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage, Request::create('testing', 'POST'));

		$authorization->registerGrant(new PasswordGrantStub);

		$token = $authorization->issueAccessToken(['grant_type' => 'password']);

		$this->assertEquals('test', $token['access_token']);
	}


	public function testIssuingAccessTokenAlsoIssuesRefreshToken()
	{
		$storage = $this->getStorageMock();

		$storage->shouldReceive('get')->with('token')->andReturn(m::mock(['create' => true, 'associateScopes' => true]));

		$authorization = new Authorization($storage, Request::create('testing', 'POST', ['grant_type' => 'password']));

		$authorization->registerGrant(new PasswordGrantStub);
		$authorization->registerGrant(new RefreshGrantStub);

		$token = $authorization->issueAccessToken();

		$this->assertEquals('test_refresh', $token['refresh_token']);
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 */
	public function testValidatingAuthorizationRequestFailsWhenAuthorizationGrantNotRegistered()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage);

		$authorization->validateAuthorizationRequest();
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 */
	public function testValidatingAuthorizationRequestFailsWhenResponseTypeIsInvalid()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage, Request::create('testing', 'POST', ['response_type' => 'test']));

		$authorization->registerGrant(new AuthorizationCodeGrantStub);

		$authorization->validateAuthorizationRequest();
	}


	public function testValidatingAuthorizationRequestSucceeds()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage, Request::create('testing', 'POST', ['response_type' => 'code']));

		$authorization->registerGrant(new AuthorizationCodeGrantStub);

		$this->assertTrue($authorization->validateAuthorizationRequest());
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 */
	public function testCreateAuthorizationCodeFailsWhenAuthorizationGrantNotRegistered()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage);

		$authorization->createAuthorizationCode('testclient', 1, 'test', []);
	}


	public function testCreateAuthorizationCodeSucceeds()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage);

		$authorization->registerGrant(new AuthorizationCodeGrantStub);

		$this->assertTrue($authorization->createAuthorizationCode('testclient', 1, 'test', []));
	}


	protected function getStorageMock()
	{
		$storage = m::mock('Dingo\OAuth2\Storage\Adapter');

		$storage->shouldReceive('get')->with('scope')->andReturn(m::mock('Dingo\OAuth2\Storage\ScopeInterface'));

		return $storage;
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

class PasswordGrantStub extends Dingo\OAuth2\Grant\Grant {

	public function execute()
	{
		return new TokenEntity('test', 'access', 'testclient', 1, strtotime(date('31/01/1991 12:00 PM')));
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

	public function createAuthorizationCode($clientId, $userId, $redirectUri, $scopes)
	{
		return true;
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