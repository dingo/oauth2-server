<?php

use Mockery as m;
use Dingo\OAuth2\Grant\Implicit;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Symfony\Component\HttpFoundation\Request;

class GrantImplicitTest extends PHPUnit_Framework_TestCase {



	public function testHandlingAuthorizationRequestReturnsTokenEntity()
	{
		$grant = (new Implicit)->setStorage($storage = $this->getStorageMock());

		// Set up the expectations so that when the create method is
		// called an AuthorizationCode entity is returned.
		$storage->shouldReceive('get')->with('token')->andReturn(m::mock([
			'create' => new TokenEntity('test', 'access', 'test', 1, $expires = time() + 120),
			'associateScopes' => true
		]));

		$token = $grant->handleAuthorizationRequest('test', 1, 'test', []);

		$this->assertEquals([
			'token' => 'test',
			'type' => 'access',
			'client_id' => 'test',
			'user_id' => 1,
			'expires' => $expires,
			'scopes' => []
		], $token->getAttributes());
	}


	public function testCorrectGrantIdentifier()
	{
		$grant = new Implicit;

		$this->assertEquals('implicit', $grant->getGrantIdentifier());
	}


	public function testCorrectResponseType()
	{
		$grant = new Implicit;
		
		$this->assertEquals('token', $grant->getResponseType());
	}


	protected function getStorageMock()
	{
		return m::mock('Dingo\OAuth2\Storage\Adapter');
	}


}