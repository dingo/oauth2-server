<?php

use Mockery as m;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;
use Dingo\OAuth2\Storage\Redis\Token as TokenStorage;

class StorageRedisTokenTest extends PHPUnit_Framework_TestCase {

	
	public function tearDown()
	{
		m::close();
	}


	public function setUp()
	{
		$this->redis = m::mock('Predis\Client');
	}


	public function testCreateTokenEntityFailsAndReturnsFalse()
	{
		$storage = new TokenStorage($this->redis, ['tokens' => 'tokens']);

		$this->redis->shouldReceive('set')->once()->with('tokens:test', '{"type":"access","client_id":"test","user_id":1,"expires":1}')->andReturn(false);

		$this->assertFalse($storage->create('test', 'access', 'test', 1, 1));
	}


	public function testCreateTokenEntitySucceedsAndReturnsTokenEntity()
	{
		$storage = new TokenStorage($this->redis, ['tokens' => 'tokens']);

		$this->redis->shouldReceive('set')->once()->with('tokens:test', '{"type":"access","client_id":"test","user_id":1,"expires":1}')->andReturn(true);

		$token = $storage->create('test', 'access', 'test', 1, 1);

		$this->assertEquals([
			'token' => 'test',
			'type' => 'access',
			'client_id' => 'test',
			'user_id' => 1,
			'expires' => 1,
			'scopes' => []
		], $token->getAttributes());
	}


	public function testAssociatingScopesToToken()
	{
		$storage = new TokenStorage($this->redis, ['token_scopes' => 'token_scopes']);

		$this->redis->shouldReceive('rpush')->once()->with('token:scopes:test', '{"scope":"foo","name":"foo","description":"foo"}')->andReturn(true);
		$this->redis->shouldReceive('rpush')->once()->with('token:scopes:test', '{"scope":"bar","name":"bar","description":"bar"}')->andReturn(true);

		$storage->associateScopes('test', [
			'foo' => new ScopeEntity('foo', 'foo', 'foo'),
			'bar' => new ScopeEntity('bar', 'bar', 'bar')
		]);
	}


	public function testGetTokenEntityFailsAndReturnsFalse()
	{
		$storage = new TokenStorage($this->redis, ['tokens' => 'tokens']);

		$this->redis->shouldReceive('get')->once()->with('tokens:test')->andReturn(false);

		$this->assertFalse($storage->get('test'));
	}


	public function testGetTokenEntitySucceedsAndReturnsTokenEntity()
	{
		$storage = new TokenStorage($this->redis, ['tokens' => 'tokens']);

		$this->redis->shouldReceive('get')->once()->with('tokens:test')->andReturn('{"type":"access","client_id":"test","user_id":1,"expires":1}');

		$token = $storage->get('test');

		$this->assertEquals([
			'token' => 'test',
			'type' => 'access',
			'client_id' => 'test',
			'user_id' => 1,
			'scopes' => [],
			'expires' => 1
		], $token->getAttributes());
	}


	public function testGetTokenWithScopesFailsAndReturnsFalse()
	{
		$storage = new TokenStorage($this->redis, ['tokens' => 'tokens']);

		$this->redis->shouldReceive('get')->once()->with('tokens:test')->andReturn(false);

		$this->assertFalse($storage->getWithScopes('test'));
	}


	public function testGetTokenWithScopesSucceedsAndReturnsTokenEntityWithAttachedScopes()
	{
		$storage = new TokenStorage($this->redis, [
			'tokens' => 'tokens',
			'scopes' => 'scopes',
			'token_scopes' => 'token_scopes'
		]);

		$this->redis->shouldReceive('get')->once()->with('tokens:test')->andReturn('{"type":"access","client_id":"test","user_id":1,"expires":1}');

		$this->redis->shouldReceive('lrange')->once()->with('token:scopes:test', 0, -1)->andReturn([
			'{"scope":"foo","name":"foo","description":"foo"}',
			'{"scope":"bar","name":"bar","description":"bar"}'
		]);

		$token = $storage->getWithScopes('test');

		$this->assertEquals([
			'token' => 'test',
			'type' => 'access',
			'client_id' => 'test',
			'user_id' => 1,
			'scopes' => [
				'foo' => new ScopeEntity('foo', 'foo', 'foo'),
				'bar' => new ScopeEntity('bar', 'bar', 'bar')
			],
			'expires' => 1
		], $token->getAttributes());
	}


	public function testDeleteToken()
	{
		$storage = new TokenStorage($this->redis, [
			'tokens' => 'tokens',
			'token_scopes' => 'token_scopes'
		]);

		$this->redis->shouldReceive('del')->once()->with('tokens:test')->andReturn(true);
		$this->redis->shouldReceive('del')->once()->with('token:scopes:test')->andReturn(true);

		$storage->delete('test');
	}

	
}