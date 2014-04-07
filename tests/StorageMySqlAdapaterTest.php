<?php

use Mockery as m;
use Dingo\OAuth2\Storage\MySqlAdapter;

class StorageMySqlAdapterTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testGetClientDriver()
	{
		$storage = new MySqlAdapter(new PDOStub);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\MySql\Client', $storage->get('client'));
	}


	public function testGetScopeDriver()
	{
		$storage = new MySqlAdapter(new PDOStub);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\MySql\Scope', $storage->get('scope'));
	}


	public function testGetAuthorizationCodeDriver()
	{
		$storage = new MySqlAdapter(new PDOStub);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\MySql\AuthorizationCode', $storage->get('authorization'));
	}


	public function testGetTokenDriver()
	{
		$storage = new MySqlAdapter(new PDOStub);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\MySql\Token', $storage->get('token'));
	}


}