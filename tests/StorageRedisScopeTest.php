<?php

use Mockery as m;
use Dingo\OAuth2\Storage\Redis\Scope as ScopeStorage;

class StorageRedisScopeTest extends PHPUnit_Framework_TestCase {

	
	public function tearDown()
	{
		m::close();
	}


	public function setUp()
	{
		$this->redis = m::mock('Predis\Client');
	}


	public function testGetScopeFailsAndReturnsFalse()
	{
		$storage = new ScopeStorage($this->redis, ['scopes' => 'scopes']);

		$this->redis->shouldReceive('get')->once()->with('scopes:test')->andReturn(false);

		$this->assertFalse($storage->get('test'));
	}


	public function testGetScopeSucceedsAndReturnsScopeEntity()
	{
		$storage = new ScopeStorage($this->redis, ['scopes' => 'scopes']);

		$this->redis->shouldReceive('get')->once()->with('scopes:test')->andReturn('{"scope":"test","name":"test","description":"test"}');

		$scope = $storage->get('test');

		$this->assertEquals([
			'scope' => 'test',
			'name' => 'test',
			'description' => 'test'
		], $scope->getAttributes());
	}


	public function testCreateScopeSucceedsAndReturnsScopeEntity()
	{
		$storage = new ScopeStorage($this->redis, ['scopes' => 'scopes']);

		$this->redis->shouldReceive('set')->once()->with('scopes:test', '{"name":"test","description":"test"}')->andReturn(true);
		$this->redis->shouldReceive('sadd')->once()->with('scopes', 'test')->andReturn(true);

		$this->assertEquals([
			'scope' => 'test',
			'name' => 'test',
			'description' => 'test'
		], $storage->create('test', 'test', 'test')->getAttributes());
	}


	public function testDeletingScope()
	{
		$storage = new ScopeStorage($this->redis, ['scopes' => 'scopes']);

		$this->redis->shouldReceive('del')->once()->with('scopes:test');
		$this->redis->shouldReceive('srem')->once()->with('scopes', 'test');

		$storage->delete('test');
	}

	
}