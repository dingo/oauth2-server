<?php

use Mockery as m;
use Dingo\OAuth2\Server\Authorization;
use Symfony\Component\HttpFoundation\Request;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Dingo\OAuth2\Entity\Client as ClientEntity;
use Dingo\OAuth2\Entity\AuthorizationCode as AuthorizationCodeEntity;

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

		$storage->shouldReceive('get')->with('token')->andReturn(m::mock([
			'create' => new TokenEntity('test_refresh', 'refresh', 'test', 1, 1),
			'associateScopes' => true
		]));

		$authorization = new Authorization($storage, Request::create('testing', 'POST', ['grant_type' => 'password']));

		$authorization->registerGrant(new PasswordGrantStub);
		$authorization->registerGrant(new RefreshGrantStub);

		$token = $authorization->issueAccessToken();

		$this->assertEquals('test_refresh', $token['refresh_token']);
	}


	public function testIssuingAccessTokenCallsAuthorizedCallback()
	{
		$storage = $this->getStorageMock();

		$storage->shouldReceive('get')->with('client')->andReturn(m::mock([
			'get' => new ClientEntity('test', 'test', 'test')
		]));

		$authorization = new Authorization($storage, Request::create('testing', 'POST', ['grant_type' => 'password']));

		$authorization->registerGrant(new PasswordGrantStub);
		$authorization->setAuthorizedCallback(function($token, $client)
		{
			$this->assertInstanceOf('Dingo\OAuth2\Entity\Token', $token);
			$this->assertInstanceOf('Dingo\OAuth2\Entity\Client', $client);
		});

		$token = $authorization->issueAccessToken();
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


	public function testHandlingAuthorizationRequestSucceeds()
	{
		$storage = $this->getStorageMock();

		$authorization = new Authorization($storage, Request::create('test', 'GET', ['response_type' => 'code', 'state' => 'teststate']));

		$authorization->registerGrant(new AuthorizationCodeGrantStub);

		$this->assertEquals([
			'code' => 'test',
			'state' => 'teststate',
			'scope' => 'testscope'
		], $authorization->handleAuthorizationRequest('testclient', 1, 'test', ['testscope' => true]));
	}


	/**
	 * @expectedException \RuntimeException
	 */
	public function testMakeRedirectUriWithoutRedirectUriInRequestAndNoDefaultRedirectUriThrowsException()
	{
		$storage = $this->getStorageMock();
		$storage->shouldReceive('get')->once()->with('client')->andReturn(m::mock([
			'get' => new ClientEntity('test', 'test', 'test')
		]));

		$authorization = new Authorization($storage, Request::create('test', 'GET', ['response_type' => 'code']));

		$authorization->makeRedirectUri(['code' => '12345']);
	}


	public function testMakeRedirectUriWithoutRedirectUriInRequestUsesDefaultRedirectUri()
	{
		$storage = $this->getStorageMock();
		$storage->shouldReceive('get')->once()->with('client')->andReturn(m::mock([
			'get' => new ClientEntity('test', 'test', 'test', 'foo.com/bar')
		]));

		$authorization = new Authorization($storage, Request::create('test', 'GET', ['response_type' => 'code']));

		$this->assertEquals('foo.com/bar?code=12345&scope=foo', $authorization->makeRedirectUri([
			'code' => '12345',
			'scope' => 'foo'
		]));
	}


	public function testMakeRedirectUriWithQueryString()
	{
		$authorization = new Authorization($this->getStorageMock(), Request::create('test', 'GET', [
			'redirect_uri' => 'foo.com/bar',
			'response_type' => 'code'
		]));

		$this->assertEquals('foo.com/bar?code=12345&scope=foo', $authorization->makeRedirectUri([
			'code' => '12345',
			'scope' => 'foo'
		]));
	}


	public function testMakeRedirectUriWithFragment()
	{
		$authorization = new Authorization($this->getStorageMock(), Request::create('test', 'GET', [
			'redirect_uri' => 'foo.com/bar',
			'response_type' => 'token'
		]));

		$this->assertEquals('foo.com/bar#access_token=12345&scope=foo', $authorization->makeRedirectUri([
			'access_token' => '12345',
			'scope' => 'foo'
		]));
	}


	protected function getStorageMock()
	{
		$storage = m::mock('Dingo\OAuth2\Storage\Adapter');

		$storage->shouldReceive('get')->with('scope')->andReturn(m::mock('Dingo\OAuth2\Storage\ScopeInterface'));

		return $storage;
	}


}