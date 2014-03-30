<?php

use Mockery as m;
use Dingo\OAuth2\Grant\AuthorizationCode;
use Symfony\Component\HttpFoundation\Request;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Dingo\OAuth2\Entity\Client as ClientEntity;
use Dingo\OAuth2\Entity\AuthorizationCode as AuthorizationCodeEntity;

class GrantAuthorizationCodeTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testValidatingAuthorizationRequestsFailsWhenMissingParameters()
	{
		$grant = new AuthorizationCode;

		$request = Request::create('test', 'GET', ['client_id' => 'test', 'redirect_uri' => 'test']);

		$grant->setRequest($request) and $grant->setStorage($this->getStorageMock());

		// Replace the query string parameters so that the missing parameter
		// is the "response_type".
		try
		{
			$grant->validateAuthorizationRequest();

			$this->fail('Exception was not thrown when there is no "response_type" parameter in query string.');
		}
		catch (Dingo\OAuth2\Exception\ClientException $e)
		{
			$this->assertEquals('The request is missing the "response_type" parameter.', $e->getMessage());
		}

		// Replace the query string parameters so that the missing parameter
		// is the "client_id".
		$request->query->replace(['response_type' => 'code', 'redirect_uri' => 'test']);

		try
		{
			$grant->validateAuthorizationRequest();

			$this->fail('Exception was not thrown when there is no "client_id" parameter in query string.');
		}
		catch (Dingo\OAuth2\Exception\ClientException $e)
		{
			$this->assertEquals('The request is missing the "client_id" parameter.', $e->getMessage());
		}

		// Replace the query string parameters so that the missing parameter
		// is the "redirect_uri".
		$request->query->replace(['response_type' => 'code', 'client_id' => 'test']);

		try
		{
			$grant->validateAuthorizationRequest();

			$this->fail('Exception was not thrown when there is no "redirect_uri" parameter in query string.');
		}
		catch (Dingo\OAuth2\Exception\ClientException $e)
		{
			$this->assertEquals('The request is missing the "redirect_uri" parameter.', $e->getMessage());
		}
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 * @expectedExceptionMessage The redirection URI is not registered to the client.
	 */
	public function testValidatingAuthorizationRequestFailsWhenPublicClientIsInvalid()
	{
		$grant = new AuthorizationCode;

		$request = Request::create('test', 'GET', ['client_id' => 'test', 'response_type' => 'code', 'redirect_uri' => 'test']);

		$grant->setRequest($request) and $grant->setStorage($storage = $this->getStorageMock());

		$storage->shouldReceive('get')->with('client')->andReturn(m::mock(['get' => false]));

		$grant->validateAuthorizationRequest();
	}


	public function testValidatingAuthorizationRequestSucceedsAndReturnsParametersArray()
	{
		$grant = new AuthorizationCode;

		$request = Request::create('test', 'GET', ['client_id' => 'test', 'response_type' => 'code', 'redirect_uri' => 'test']);

		$grant->setRequest($request) and $grant->setStorage($storage = $this->getStorageMock());
		$grant->setScopeValidator($validator = m::mock('Dingo\OAuth2\ScopeValidator'));

		// Set up the expectations on the validator and the storage so that
		// it proceeds through the scope validation and retrieves a
		// client from the storage.
		$validator->shouldReceive('validate')->once()->andReturn([]);

		$storage->shouldReceive('get')->with('client')->andReturn(m::mock(['get' => true]));

		$parameters = $grant->validateAuthorizationRequest();

		$this->assertEquals('test', $parameters['client_id']);
	}


	public function testHandlingAuthorizationRequestReturnsAuthorizationCodeEntity()
	{
		$grant = (new AuthorizationCode)->setStorage($storage = $this->getStorageMock());

		// Set up the expectations so that when the create method is
		// called an AuthorizationCode entity is returned.
		$storage->shouldReceive('get')->with('authorization')->andReturn(m::mock([
			'create' => new AuthorizationCodeEntity('test', 'test', 1, 'test', $expires = time() + 120),
			'associateScopes' => true
		]));

		$code = $grant->handleAuthorizationRequest('test', 1, 'test', []);

		$this->assertEquals([
			'code' => 'test',
			'client_id' => 'test',
			'user_id' => 1,
			'redirect_uri' => 'test',
			'expires' => $expires,
			'scopes' => []
		], $code->getAttributes());
	}


	public function testExecutingGrantFlowThrowsExceptionWhenMissingRequiredParameters()
	{
		$grant = new AuthorizationCode;

		$request = Request::create('test', 'GET');

		$grant->setRequest($request) and $grant->setStorage($this->getStorageMock());

		// Replace the query string parameters so that the missing parameter
		// is the "redirect_uri".
		$request->query->replace(['code' => 'test']);

		try
		{
			$grant->execute();

			$this->fail('Exception was not thrown when there is no "redirect_uri" parameter in query string.');
		}
		catch (Dingo\OAuth2\Exception\ClientException $e)
		{
			$this->assertEquals('The request is missing the "redirect_uri" parameter.', $e->getMessage());
		}

		// Replace the query string parameters so that the missing parameter
		// is the "code".
		$request->query->replace(['redirect_uri' => 'test']);

		try
		{
			$grant->execute();

			$this->fail('Exception was not thrown when there is no "code" parameter in query string.');
		}
		catch (Dingo\OAuth2\Exception\ClientException $e)
		{
			$this->assertEquals('The request is missing the "code" parameter.', $e->getMessage());
		}
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 * @expectedExceptionMessage The authorization code does not exist.
	 */
	public function testExecutingGrantFlowThrowsExceptionWhenAuthorizationCodeIsInvalid()
	{
		$grant = new AuthorizationCode;

		$request = Request::create('test', 'GET', [
			'client_id' => 'test',
			'client_secret' => 'test',
			'redirect_uri' => 'test',
			'code' => 'test'
		]);

		$grant->setRequest($request) and $grant->setStorage($storage = $this->getStorageMock());

		$storage->shouldReceive('get')->with('authorization')->andReturn(m::mock(['get' => false]));

		$grant->execute();
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 * @expectedExceptionMessage The authorization code is not associated with the client.
	 */
	public function testExecutingGrantFlowThrowsExceptionWhenClientsDoNotMatch()
	{
		$grant = new AuthorizationCode;

		$request = Request::create('test', 'GET', [
			'client_id' => 'test',
			'client_secret' => 'test',
			'redirect_uri' => 'test',
			'code' => 'test'
		]);

		$grant->setRequest($request) and $grant->setStorage($storage = $this->getStorageMock());

		$storage->shouldReceive('get')->with('client')->andReturn(m::mock([
			'get' => new ClientEntity('foo', 'foo', 'foo')
		]));

		$storage->shouldReceive('get')->with('authorization')->andReturn(m::mock([
			'get' => new AuthorizationCodeEntity('test', 'test', 1, 'test', time() + 120)
		]));

		$grant->execute();
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 * @expectedExceptionMessage The redirection URI does not match the redirection URI of the authorization code.
	 */
	public function testExecutingGrantFlowThrowsExceptionWhenRedirectionUrisDoNotMatch()
	{
		$grant = new AuthorizationCode;

		$request = Request::create('test', 'GET', [
			'client_id' => 'test',
			'client_secret' => 'test',
			'redirect_uri' => 'test',
			'code' => 'test'
		]);

		$grant->setRequest($request) and $grant->setStorage($storage = $this->getStorageMock());

		$storage->shouldReceive('get')->with('client')->andReturn(m::mock([
			'get' => new ClientEntity('test', 'test', 'test')
		]));

		$storage->shouldReceive('get')->with('authorization')->andReturn(m::mock([
			'get' => new AuthorizationCodeEntity('test', 'test', 1, 'foo', time() + 120)
		]));

		$grant->execute();
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 * @expectedExceptionMessage The authorization code has expired.
	 */
	public function testExecutingGrantFlowThrowsExceptionWhenAuthorizationCodeHasExpired()
	{
		$grant = new AuthorizationCode;

		$request = Request::create('test', 'GET', [
			'client_id' => 'test',
			'client_secret' => 'test',
			'redirect_uri' => 'test',
			'code' => 'test'
		]);

		$grant->setRequest($request) and $grant->setStorage($storage = $this->getStorageMock());

		$storage->shouldReceive('get')->with('client')->andReturn(m::mock([
			'get' => new ClientEntity('test', 'test', 'test')
		]));

		$storage->shouldReceive('get')->with('authorization')->andReturn(m::mock([
			'get' => new AuthorizationCodeEntity('test', 'test', 1, 'test', time() - 120)
		]));

		$grant->execute();
	}


	public function testExecutingGrantFlowSucceedsAndReturnsTokenEntity()
	{
		$grant = new AuthorizationCode;

		$request = Request::create('test', 'GET', [
			'client_id' => 'test',
			'client_secret' => 'test',
			'redirect_uri' => 'test',
			'code' => 'test'
		]);

		$grant->setRequest($request) and $grant->setStorage($storage = $this->getStorageMock());

		$storage->shouldReceive('get')->with('client')->andReturn(m::mock([
			'get' => new ClientEntity('test', 'test', 'test')
		]));

		$storage->shouldReceive('get')->with('authorization')->andReturn(m::mock([
			'get' => (new AuthorizationCodeEntity('test', 'test', 1, 'test', time() + 120))->attachScopes(['test' => true]),
			'delete' => true
		]));

		$storage->shouldReceive('get')->with('token')->andReturn(m::mock([
			'create' => new TokenEntity('test', 'access', 'test', 1, $expires = time() + 120),
			'associateScopes' => true
		]));

		$token = $grant->execute();

		$this->assertEquals([
			'token' => 'test',
			'type' => 'access',
			'client_id' => 'test',
			'user_id' => 1,
			'expires' => $expires,
			'scopes' => [
				'test' => true
			]
		], $token->getAttributes());
	}


	public function testCorrectGrantIdentifier()
	{
		$grant = new AuthorizationCode;

		$this->assertEquals('authorization_code', $grant->getGrantIdentifier());
	}


	public function testCorrectResponseType()
	{
		$grant = new AuthorizationCode;

		$this->assertEquals('code', $grant->getResponseType());
	}


	protected function getStorageMock()
	{
		return m::mock('Dingo\OAuth2\Storage\Adapter');
	}


}