<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;
use Dingo\OAuth2\Entity\Client as ClientEntity;

class GrantGrantTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testValidatingConfidentialClientGetIdAndSecretFromAuthorizationHeader()
	{
		$grant = new GrantStub;

		$request = Request::create('http://test:test@test', 'GET');
		$request->headers->set('authorization', base64_encode('test:test'));

		$grant->setRequest($request) and $grant->setStorage($storage = $this->getStorageMock());

		$storage->shouldReceive('get')->once()->with('client')->andReturn($client = m::mock('Dingo\OAuth2\Storage\ClientInterface'));
		$client->shouldReceive('get')->once()->with('test', 'test', null)->andReturn(new ClientEntity('test', 'test', 'test'));

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => null
		], $grant->execute()->getAttributes());
	}


	public function testValidatingConfidentialClientGetIdAndSecretFromUri()
	{
		$grant = new GrantStub;

		$request = Request::create('test', 'GET', ['client_id' => 'test', 'client_secret' => 'test']);

		$grant->setRequest($request) and $grant->setStorage($storage = $this->getStorageMock());

		$storage->shouldReceive('get')->once()->with('client')->andReturn($client = m::mock('Dingo\OAuth2\Storage\ClientInterface'));
		$client->shouldReceive('get')->once()->with('test', 'test', null)->andReturn(new ClientEntity('test', 'test', 'test'));

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => null
		], $grant->execute()->getAttributes());
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 */
	public function testValidatingConfidentialClientFailsWhenUnableToGetIdAndSecret()
	{
		$grant = new GrantStub;

		$request = Request::create('test', 'GET');

		$grant->setRequest($request) and $grant->setStorage($storage = $this->getStorageMock());

		$grant->execute();
	}


	protected function getStorageMock()
	{
		return m::mock('Dingo\OAuth2\Storage\Adapter');
	}


}