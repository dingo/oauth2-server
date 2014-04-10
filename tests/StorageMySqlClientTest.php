<?php

use Dingo\OAuth2\Entity\Client as ClientEntity;
use Dingo\OAuth2\Storage\MySql\Client as ClientStorage;

class StorageMySqlClientTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->pdo = $this->getMock('PDOStub');
	}


	public function tearDown()
	{
		unset($this->pdo);
	}


	public function testGetClientByIdFailsAndReturnsFalse()
	{
		$storage = new ClientStorage($this->pdo, ['clients' => 'clients']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(false));

		$this->assertFalse($storage->get('test'));
	}


	public function testGetClientByIdSucceedsAndRedirectionUriIsNotFound()
	{
		$storage = new ClientStorage($this->pdo, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->pdo->expects($this->at(0))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'trusted' => false
		]));

		$this->pdo->expects($this->at(1))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(false));

		$client = $storage->get('test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => null,
			'trusted' => false
		], $client->getAttributes());
	}


	public function testGetClientByIdPullsFromCacheOnSecondCall()
	{
		$storage = new ClientStorage($this->pdo, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->pdo->expects($this->at(0))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'trusted' => false
		]));

		$this->pdo->expects($this->at(1))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(false));

		$storage->get('test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => null,
			'trusted' => false
		], $storage->get('test')->getAttributes());
	}


	public function testGetClientByIdSucceedsAndRedirectionUriIsFound()
	{
		$storage = new ClientStorage($this->pdo, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->pdo->expects($this->at(0))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'trusted' => false
		]));

		$this->pdo->expects($this->at(1))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'uri' => 'test'
		]));

		$client = $storage->get('test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => 'test',
			'trusted' => false
		], $client->getAttributes());
	}


	public function testGetClientByIdAndRedirectionUriSucceeds()
	{
		$storage = new ClientStorage($this->pdo, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => 'test',
			'trusted' => false
		]));

		$client = $storage->get('test', null, 'test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => 'test',
			'trusted' => false
		], $client->getAttributes());
	}


	public function testGetClientByIdAndSecretSucceeds()
	{
		$storage = new ClientStorage($this->pdo, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->pdo->expects($this->at(0))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'trusted' => false
		]));

		$this->pdo->expects($this->at(1))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(false));

		$client = $storage->get('test', 'test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => null,
			'trusted' => false
		], $client->getAttributes());
	}


	public function testGetClientByIdAndSecretAndRedirectionUriSucceeds()
	{
		$storage = new ClientStorage($this->pdo, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => 'test',
			'trusted' => false
		]));

		$client = $storage->get('test', 'test', 'test');

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => 'test',
			'trusted' => false
		], $client->getAttributes());
	}


	public function testCreateClientWithRedirectionUrisSucceedsAndReturnsClientEntity()
	{
		$storage = new ClientStorage($this->pdo, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->pdo->expects($this->at(0))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));

		$this->pdo->expects($this->at(1))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->any())->method('execute')->will($this->returnValue(true));

		$this->assertEquals([
			'id' => 'test',
			'secret' => 'test',
			'name' => 'test',
			'redirect_uri' => 'foo',
			'trusted' => false
		], $storage->create('test', 'test', 'test', [['uri' => 'foo', 'default' => true],['uri' => 'bar', 'default' => false]])->getAttributes());
	}


	public function testDeletingClient()
	{
		$storage = new ClientStorage($this->pdo, ['clients' => 'clients', 'client_endpoints' => 'client_endpoints']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));

		$storage->delete('test');
	}


}