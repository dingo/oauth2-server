<?php

use Mockery as m;
use Dingo\OAuth2\Grant\ClientCredentials;
use Symfony\Component\HttpFoundation\Request;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Dingo\OAuth2\Entity\Client as ClientEntity;

class GrantClientCredentialsTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testExecutingGrantFlowSucceedsAndReturnsValidToken()
	{
		$grant = (new ClientCredentials)
			->setRequest(Request::create('test', 'GET', [
				'client_id' => 'test',
				'client_secret' => 'test'
			]))
			->setStorage($storage = $this->getStorageMock())
			->setScopeValidator($validator = m::mock('Dingo\OAuth2\ScopeValidator'));

		$validator->shouldReceive('validate')->once()->andReturn(['test' => true]);

		$storage->shouldReceive('get')->with('client')->andReturn(m::mock([
			'get' => new ClientEntity('test', 'test', 'test')
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
		$grant = new ClientCredentials;

		$this->assertEquals('client_credentials', $grant->getGrantIdentifier());
	}


	protected function getStorageMock()
	{
		return m::mock('Dingo\OAuth2\Storage\Adapter');
	}


}