<?php

use Dingo\OAuth2\Storage\Adapter;

class StorageAdapterTest extends PHPUnit_Framework_TestCase {


	public function testCanGetStorageDriver()
	{
		$storage = new AdapterStub;

		$this->assertEquals('client', $storage->get('client'));
	}


	/**
	 * @expectedException \RuntimeException
	 * @expectedExceptionMessage Storage driver [invalid] is not supported.
	 */
	public function testGettingInvalidStorageThrowsRuntimeException()
	{
		$storage = new AdapterStub;

		$storage->get('invalid');
	}


}