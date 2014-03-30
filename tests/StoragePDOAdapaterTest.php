<?php

use Mockery as m;
use Dingo\OAuth2\Storage\PDOAdapter;

class StoragePDOAdapterTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testGetClientDriver()
	{
		$storage = new PDOAdapter(new PDOStub);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\PDO\Client', $storage->get('client'));
	}


	public function testGetScopeDriver()
	{
		$storage = new PDOAdapter(new PDOStub);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\PDO\Scope', $storage->get('scope'));
	}


	public function testGetAuthorizationCodeDriver()
	{
		$storage = new PDOAdapter(new PDOStub);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\PDO\AuthorizationCode', $storage->get('authorization'));
	}


	public function testGetTokenDriver()
	{
		$storage = new PDOAdapter(new PDOStub);

		$this->assertInstanceOf('Dingo\OAuth2\Storage\PDO\Token', $storage->get('token'));
	}


}