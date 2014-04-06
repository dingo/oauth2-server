<?php

use Mockery as m;
use Dingo\OAuth2\Storage\RedisAdapter;

class StorageRedisAdapterTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testGetClientDriver()
	{
		$storage = new RedisAdapter(new Predis\Client);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\Redis\Client', $storage->get('client'));
	}


	public function testGetScopeDriver()
	{
		$storage = new RedisAdapter(new Predis\Client);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\Redis\Scope', $storage->get('scope'));
	}


	public function testGetAuthorizationCodeDriver()
	{
		$storage = new RedisAdapter(new Predis\Client);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\Redis\AuthorizationCode', $storage->get('authorization'));
	}


	public function testGetTokenDriver()
	{
		$storage = new RedisAdapter(new Predis\Client);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\Redis\Token', $storage->get('token'));
	}


}