<?php

use Mockery as m;
use Dingo\OAuth2\Grant\Password;
use Symfony\Component\HttpFoundation\Request;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Dingo\OAuth2\Entity\Client as ClientEntity;

class GrantPasswordTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testValidatingRequestParametersFailsWhenMissingParameters()
	{
		$grant = (new Password)->setRequest($request = Request::create('test', 'GET'));

		// Replace the query string parameters so that the missing parameter
		// is the "username".
		$request->query->replace(['password' => 'test']);

		try
		{
			$grant->execute();

			$this->fail('Exception was not thrown when there is no "username" parameter in query string.');
		}
		catch (Dingo\OAuth2\Exception\ClientException $e)
		{
			$this->assertEquals('The request is missing the "username" parameter.', $e->getMessage());
		}

		// Replace the query string parameters so that the missing parameter
		// is the "password".
		$request->query->replace(['username' => 'test']);

		try
		{
			$grant->execute();

			$this->fail('Exception was not thrown when there is no "password" parameter in query string.');
		}
		catch (Dingo\OAuth2\Exception\ClientException $e)
		{
			$this->assertEquals('The request is missing the "password" parameter.', $e->getMessage());
		}
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 * @expectedExceptionMessage The user credentials failed to authenticate.
	 */
	public function testExecutingGrantFlowFailsWithInvalidUserCredentialsAndThatCorrectUserCredentialsAreGivenToAuthenticationCallback()
	{
		$grant = (new Password)->setRequest(Request::create('test', 'GET', [
			'username' => 'foo', 'password' => 'bar'
		]));

		$grant->setAuthenticationCallback(function($username, $password)
		{
			$this->assertEquals('foo', $username);
			$this->assertEquals('bar', $password);

			return false;
		});

		$grant->execute();
	}


	public function testExecutingGrantFlowSucceedsAndReturnsValidToken()
	{
		$grant = (new Password)
			->setRequest(Request::create('test', 'GET', [
				'username' => 'foo', 'password' => 'bar', 'client_id' => 'test', 'client_secret' => 'test'
			]))
			->setStorage($storage = $this->getStorageMock())
			->setScopeValidator($validator = m::mock('Dingo\OAuth2\ScopeValidator'));

		$validator->shouldReceive('validate')->once()->andReturn(['test' => true]);

		$grant->setAuthenticationCallback(function($username, $password)
		{
			return true;
		});

		$storage->shouldReceive('get')->with('client')->andReturn(m::mock([
			'get' => new ClientEntity('test', 'test', 'test', false)
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
		$grant = new Password;

		$this->assertEquals('password', $grant->getGrantIdentifier());
	}


	protected function getStorageMock()
	{
		return m::mock('Dingo\OAuth2\Storage\Adapter');
	}


}