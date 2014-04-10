<?php

use Mockery as m;
use Dingo\OAuth2\Grant\RefreshToken;
use Symfony\Component\HttpFoundation\Request;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Dingo\OAuth2\Entity\Client as ClientEntity;

class GrantRefreshTokenTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testValidatingRequestParametersFailsWhenMissingParameters()
	{
		$grant = (new RefreshToken)->setRequest($request = Request::create('test', 'GET'));

		try
		{
			$grant->execute();

			$this->fail('Exception was not thrown when there is no "refresh_token" parameter in query string.');
		}
		catch (Dingo\OAuth2\Exception\ClientException $e)
		{
			$this->assertEquals('The request is missing the "refresh_token" parameter.', $e->getMessage());
		}
	}


	public function testExecutingGrantFlowSucceedsAndReturnsValidToken()
	{
		$grant = (new RefreshToken)
			->setRequest(Request::create('test', 'GET', [
				'refresh_token' => 'test',
				'client_id' => 'test',
				'client_secret' => 'test'
			]))
			->setStorage($storage = $this->getStorageMock())
			->setScopeValidator($validator = m::mock('Dingo\OAuth2\ScopeValidator'));

		$validator->shouldReceive('validate')->once()->andReturn(['test' => true]);

		$storage->shouldReceive('get')->with('client')->andReturn(m::mock([
			'get' => new ClientEntity('test', 'test', 'test', false)
		]));

		$storage->shouldReceive('get')->with('token')->andReturn(m::mock([
			'getWithScopes' => new TokenEntity('test', 'refresh', 'test', 1, $expires = time() + 120),
			'create' => new TokenEntity('test', 'refresh', 'test', 1, $expires = time() + 120),
			'associateScopes' => true,
			'delete' => true
		]));

		$token = $grant->execute();

		$this->assertEquals([
			'token' => 'test',
			'type' => 'refresh',
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
		$grant = new RefreshToken;

		$this->assertEquals('refresh_token', $grant->getGrantIdentifier());
	}


	protected function getStorageMock()
	{
		return m::mock('Dingo\OAuth2\Storage\Adapter');
	}


}