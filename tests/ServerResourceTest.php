<?php

use Mockery as m;
use Dingo\OAuth2\Server\Resource;
use Symfony\Component\HttpFoundation\Request;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;

class ServerResourceTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testCanGetAccessTokenFromHeaders()
	{
		$request = Request::create('foo', 'POST');
		$request->headers->set('authorization', 'Bearer 12345');

		$storage = $this->getStorageMock();

		$resource = new Resource($storage, $request);

		$this->assertEquals('12345', $resource->getAccessToken());
	}


	public function testCanGetAccessTokenFromPostRequestBody()
	{
		$request = Request::create('foo', 'POST', ['access_token' => '12345']);

		$storage = $this->getStorageMock();

		$resource = new Resource($storage, $request);

		$this->assertEquals('12345', $resource->getAccessToken());
	}


	public function testCanGetAccessTokenFromGetQueryParameters()
	{
		$request = Request::create('foo', 'GET', ['access_token' => '12345']);

		$storage = $this->getStorageMock();

		$resource = new Resource($storage, $request);

		$this->assertEquals('12345', $resource->getAccessToken());
	}


	public function testGettingAccessTokenReturnsFalseWhenTokenNotFound()
	{
		$request = Request::create('foo', 'GET');

		$storage = $this->getStorageMock();

		$resource = new Resource($storage, $request);

		$this->assertFalse($resource->getAccessToken());
	}


	public function testGettingAccessTokenReturnsFalseWhenTokenNotFoundInAuthorizationHeader()
	{
		$request = Request::create('foo', 'GET');

		$request->headers->set('authorization', 'test');

		$storage = $this->getStorageMock();

		$resource = new Resource($storage, $request);

		$this->assertFalse($resource->getAccessToken());
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\InvalidTokenException
	 */
	public function testValidatingResourceWithNoAccessTokenThrowsException()
	{
		$request = Request::create('foo', 'GET');

		$storage = $this->getStorageMock();

		$resource = new Resource($storage, $request);

		$resource->validateRequest();
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\InvalidTokenException
	 */
	public function testValidatingResourceWithUnknownTokenThrowsException()
	{
		$request = Request::create('foo', 'GET', ['access_token' => 12345]);

		$storage = $this->getStorageMock();

		$storage->shouldReceive('get')->with('token')->andReturn(m::mock(['getWithScopes' => false]));

		$resource = new Resource($storage, $request);

		$resource->validateRequest();
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\InvalidTokenException
	 */
	public function testValidatingResourceWithExpiredTokenThrowsException()
	{
		$request = Request::create('foo', 'GET', ['access_token' => 12345]);

		$storage = $this->getStorageMock();

		$storage->shouldReceive('get')->with('token')->andReturn(m::mock([
			'getWithScopes' => new TokenEntity(12345, 'access', 'test', 1, time() - 3600),
			'delete' => true
		]));


		$resource = new Resource($storage, $request);

		$resource->validateRequest();
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\InvalidTokenException
	 */
	public function testValidatingResourceWithUnassociatedScopeThrowsException()
	{
		$request = Request::create('foo', 'GET', ['access_token' => 12345]);

		$storage = $this->getStorageMock();

		$storage->shouldReceive('get')->with('token')->andReturn(m::mock([
			'getWithScopes' => new TokenEntity(12345, 'access', 'test', 1, time() + 3600),
			'delete' => true
		]));


		$resource = new Resource($storage, $request);

		$resource->validateRequest('foo');
	}


	public function testValidatingResourceWithValidTokenSucceeds()
	{
		$request = Request::create('foo', 'GET', ['access_token' => 12345]);

		$storage = $this->getStorageMock();

		$storage->shouldReceive('get')->with('token')->andReturn(m::mock([
			'getWithScopes' => new TokenEntity(12345, 'access', 'test', 1, time() + 3600)
		]));


		$resource = new Resource($storage, $request);

		$token = $resource->validateRequest();

		$this->assertEquals(12345, $token->getToken());
	}


	public function testValidatingResourceWithValidTokenAndDefaultScopesSucceeds()
	{
		$request = Request::create('foo', 'GET', ['access_token' => 12345]);

		$storage = $this->getStorageMock();

		$token = (new TokenEntity(12345, 'access', 'test', 1, time() + 3600))->attachScopes([
			'foo' => new ScopeEntity('foo', 'foo', 'foo')
		]);

		$storage->shouldReceive('get')->with('token')->andReturn(m::mock(['getWithScopes' => $token]));

		$resource = (new Resource($storage, $request))->setDefaultScopes(['foo']);

		$token = $resource->validateRequest();

		$this->assertEquals(12345, $token->getToken());
	}


	protected function getStorageMock()
	{
		$storage = m::mock('Dingo\OAuth2\Storage\Adapter');

		return $storage;
	}


}