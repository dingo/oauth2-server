<?php

use Mockery as m;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;
use Dingo\OAuth2\Storage\Redis\AuthorizationCode as AuthorizationCodeStorage;

class StorageRedisAuthorizationCodeTest extends PHPUnit_Framework_TestCase {

	
	public function tearDown()
	{
		m::close();
	}


	public function setUp()
	{
		$this->redis = m::mock('Predis\Client');
	}


	public function testCreateAuthorizationCodeEntityFailsAndReturnsFalse()
	{
		$storage = new AuthorizationCodeStorage($this->redis, ['authorization_codes' => 'authorization_codes']);

		$this->redis->shouldReceive('set')->once()->with('authorization:codes:test', '{"client_id":"test","user_id":1,"redirect_uri":"test","expires":1}')->andReturn(false);

		$this->assertFalse($storage->create('test', 'test', 1, 'test', 1));
	}


	public function testCreateAuthorizationCodeEntitySucceedsAndReturnsAuthorizationCodeEntity()
	{
		$storage = new AuthorizationCodeStorage($this->redis, ['authorization_codes' => 'authorization_codes']);

		$this->redis->shouldReceive('set')->once()->with('authorization:codes:test', '{"client_id":"test","user_id":1,"redirect_uri":"test","expires":1}')->andReturn(true);

		$code = $storage->create('test', 'test', 1, 'test', 1);

		$this->assertEquals([
			'code' => 'test',
			'client_id' => 'test',
			'user_id' => 1,
			'redirect_uri' => 'test',
			'expires' => 1,
			'scopes' => []
		], $code->getAttributes());
	}


	public function testAssociatingScopesToAuthorizationCode()
	{
		$storage = new AuthorizationCodeStorage($this->redis, ['authorization_code_scopes' => 'authorization_code_scopes']);

		$this->redis->shouldReceive('rpush')->once()->with('authorization:code:scopes:test', '{"scope":"foo","name":"foo","description":"foo"}')->andReturn(true);
		$this->redis->shouldReceive('rpush')->once()->with('authorization:code:scopes:test', '{"scope":"bar","name":"bar","description":"bar"}')->andReturn(true);

		$storage->associateScopes('test', [
			'foo' => new ScopeEntity('foo', 'foo', 'foo'),
			'bar' => new ScopeEntity('bar', 'bar', 'bar')
		]);
	}


	public function testGetAuthorizationCodeEntityFailsAndReturnsFalse()
	{
		$storage = new AuthorizationCodeStorage($this->redis, ['authorization_codes' => 'authorization_codes']);

		$this->redis->shouldReceive('get')->once()->with('authorization:codes:test')->andReturn(false);

		$this->assertFalse($storage->get('test'));
	}


	public function testGetAuthorizationCodeEntitySucceedsAndReturnsAuthorizationCodeEntity()
	{
		$storage = new AuthorizationCodeStorage($this->redis, [
			'authorization_codes' => 'authorization_codes',
			'scopes' => 'scopes',
			'authorization_code_scopes' => 'authorization_code_scopes'
		]);

		$this->redis->shouldReceive('get')->once()->with('authorization:codes:test')->andReturn('{"client_id":"test","user_id":1,"redirect_uri":"test","expires":1}');
		$this->redis->shouldReceive('lrange')->once()->with('authorization:code:scopes:test', 0, -1)->andReturn([
			'{"scope":"foo","name":"foo","description":"foo"}',
			'{"scope":"bar","name":"bar","description":"bar"}'
		]);

		$code = $storage->get('test');

		$this->assertEquals([
			'code' => 'test',
			'client_id' => 'test',
			'user_id' => 1,
			'redirect_uri' => 'test',
			'scopes' => [
				'foo' => new ScopeEntity('foo', 'foo', 'foo'),
				'bar' => new ScopeEntity('bar', 'bar', 'bar')
			],
			'expires' => 1
		], $code->getAttributes());
	}


	public function testDeleteAuthorizationCode()
	{
		$storage = new AuthorizationCodeStorage($this->redis, [
			'authorization_codes' => 'authorization_codes',
			'authorization_code_scopes' => 'authorization_code_scopes'
		]);

		$this->redis->shouldReceive('del')->once()->with('authorization:codes:test')->andReturn(true);
		$this->redis->shouldReceive('del')->once()->with('authorization:code:scopes:test')->andReturn(true);

		$storage->delete('test');
	}



	
}