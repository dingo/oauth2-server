<?php

use Mockery as m;
use Dingo\OAuth2\Entity\Client as ClientEntity;
use Dingo\OAuth2\Storage\Redis\Client as ClientStorage;

class StorageRedisClientTest extends PHPUnit_Framework_TestCase {

	
	public function tearDown()
	{
		m::close();
	}


	public function setUp()
	{
		$this->redis = m::mock('Predis\Client');
	}


	public function testGetClientByIdFailsAndReturnsFalse()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients']);

		$this->redis->shouldReceive('get')->once()->with('clients:test')->andReturn(false);

		$this->assertFalse($storage->get('test'));
	}


	public function testGetClientByIdAndSecretAndRedirectionUriFailsAndReturnsFalse()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->redis->shouldReceive('get')->once()->with('clients:test')->andReturn('{"secret":"test","name":"test"}');
		$this->redis->shouldReceive('smembers')->once()->with('client:endpoints:test')->andReturn([
			'{"uri":"bar","is_default":false}'
		]);

		$this->assertFalse($storage->get('test', 'bad', 'bad'));
	}


	public function testGetClientByIdAndSecretFailsAndReturnsFalse()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->redis->shouldReceive('get')->once()->with('clients:test')->andReturn('{"secret":"test","name":"test"}');
		$this->redis->shouldReceive('smembers')->once()->with('client:endpoints:test')->andReturn([]);

		$this->assertFalse($storage->get('test', 'bad'));
	}


	public function testGetClientByIdAndRedirectionUriFailsAndReturnsFalse()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->redis->shouldReceive('get')->once()->with('clients:test')->andReturn('{"secret":"test","name":"test"}');
		$this->redis->shouldReceive('smembers')->once()->with('client:endpoints:test')->andReturn([
			'{"uri":"bar","is_default":false}'
		]);

		$this->assertFalse($storage->get('test', null, 'bad'));
	}


	public function testGetClientByIdSucceedsAndRedirectionUriIsNotFound()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->redis->shouldReceive('get')->once()->with('clients:test')->andReturn('{"secret":"test","name":"test"}');
		$this->redis->shouldReceive('smembers')->twice()->with('client:endpoints:test')->andReturn([]);

		$client = $storage->get('test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => null
		], $client->getAttributes());
	}


	public function testGetClientByIdSucceedsAndRedirectionUriIsFound()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->redis->shouldReceive('get')->once()->with('clients:test')->andReturn('{"secret":"test","name":"test"}');
		$this->redis->shouldReceive('smembers')->twice()->with('client:endpoints:test')->andReturn([
			'{"uri":"test","is_default":true}'
		]);

		$client = $storage->get('test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => 'test'
		], $client->getAttributes());
	}


	public function testGetClientByIdAndRedirectionUriSucceeds()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->redis->shouldReceive('get')->once()->with('clients:test')->andReturn('{"secret":"test","name":"test"}');
		$this->redis->shouldReceive('smembers')->once()->with('client:endpoints:test')->andReturn([
			'{"uri":"test","is_default":false}'
		]);

		$client = $storage->get('test', null, 'test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => 'test'
		], $client->getAttributes());
	}


	public function testGetClientByIdAndSecretSucceeds()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->redis->shouldReceive('get')->once()->with('clients:test')->andReturn('{"secret":"test","name":"test"}');
		$this->redis->shouldReceive('smembers')->twice()->with('client:endpoints:test')->andReturn([]);

		$client = $storage->get('test', 'test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => null
		], $client->getAttributes());
	}


	public function testGetClientByIdAndSecretAndRedirectionUriSucceeds()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->redis->shouldReceive('get')->once()->with('clients:test')->andReturn('{"secret":"test","name":"test"}');
		$this->redis->shouldReceive('smembers')->once()->with('client:endpoints:test')->andReturn([
			'{"uri":"test","is_default":false}'
		]);

		$client = $storage->get('test', 'test', 'test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => 'test'
		], $client->getAttributes());
	}


	public function testCreateClientSucceedsAndReturnsClientEntity()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients']);

		$this->redis->shouldReceive('set')->once()->with('clients:test', '{"secret":"test","name":"test"}')->andReturn(true);
		$this->redis->shouldReceive('sadd')->once()->with('clients', 'test')->andReturn(true);

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => null
		], $storage->create('test', 'test', 'test')->getAttributes());
	}


	public function testCreateClientWithRedirectionUrisSucceedsAndReturnsClientEntity()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->redis->shouldReceive('set')->once()->with('clients:test', '{"secret":"test","name":"test"}')->andReturn(true);
		$this->redis->shouldReceive('sadd')->once()->with('clients', 'test')->andReturn(true);
		$this->redis->shouldReceive('sadd')->once()->with('client:endpoints:test', '{"uri":"foo","is_default":true}')->andReturn(true);
		$this->redis->shouldReceive('sadd')->once()->with('client:endpoints:test', '{"uri":"bar","is_default":false}')->andReturn(true);

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => 'foo'
		], $storage->create('test', 'test', 'test', [['uri' => 'foo', 'default' => true],['uri' => 'bar', 'default' => false]])->getAttributes());
	}


	public function testDeletingClient()
	{
		$storage = new ClientStorage($this->redis, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->redis->shouldReceive('del')->once()->with('clients:test')->andReturn(true);
		$this->redis->shouldReceive('srem')->once()->with('clients', 'test')->andReturn(true);
		$this->redis->shouldReceive('del')->once()->with('client:endpoints:test')->andReturn(true);

		$storage->delete('test');
	}
	
	
}