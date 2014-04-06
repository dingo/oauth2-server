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

	
}