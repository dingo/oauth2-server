<?php

use Mockery as m;

class StorageRedisRedisTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function setUp()
	{
		$this->redis = m::mock('Predis\Client');
		$this->storage = new RedisStub($this->redis, []);
	}


	public function testGetValueTwicePullsFromCacheOnSecondTime()
	{
		$this->redis->shouldReceive('get')->once()->with('test:foo')->andReturn('bar');

		$this->assertEquals('bar', $this->storage->getValue('foo', 'test'));
		$this->assertEquals('bar', $this->storage->getValue('foo', 'test'));
	}


	public function testGetUnknownKeyReturnsFalse()
	{
		$this->redis->shouldReceive('get')->once()->with('test:foo')->andReturn(false);

		$this->assertFalse($this->storage->getValue('foo', 'test'));
	}


	public function testSettingValueThenGettingValuePullsFromCache()
	{
		$this->redis->shouldReceive('set')->once()->with('test:foo', 'bar')->andReturn(true);

		$this->storage->setValue('foo', 'test', 'bar');

		$this->assertEquals('bar', $this->storage->getValue('foo', 'test'));
	}


	public function testGetValueFromListTwicePullsFromCacheOnSecondTime()
	{
		$this->redis->shouldReceive('lrange')->once()->with('test:foo', 0, -1)->andReturn(['bar']);

		$this->assertEquals(['bar'], $this->storage->getList('foo', 'test'));
		$this->assertEquals(['bar'], $this->storage->getList('foo', 'test'));
	}


	public function testPushValueOntoListThenGettingValuePullsFromCache()
	{
		$this->redis->shouldReceive('rpush')->once()->with('test:foo', 'bar')->andReturn(true);
		$this->redis->shouldReceive('rpush')->once()->with('test:foo', 'baz')->andReturn(true);

		$this->storage->pushList('foo', 'test', 'bar');
		$this->storage->pushList('foo', 'test', 'baz');

		$this->assertEquals(['bar', 'baz'], $this->storage->getList('foo', 'test'));
	}


	public function testDeletingKeyAlsoUnsetsCachedValue()
	{
		$this->redis->shouldReceive('set')->once()->with('test:foo', 'bar')->andReturn(true);
		$this->redis->shouldReceive('del')->once()->with('test:foo')->andReturn(true);

		$this->storage->setValue('foo', 'test', 'bar');

		$this->assertTrue($this->storage->deleteKey('foo', 'test'));
	}


}