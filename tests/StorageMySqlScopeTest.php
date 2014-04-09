<?php

use Dingo\OAuth2\Entity\Scope as ScopeEntity;
use Dingo\OAuth2\Storage\MySql\Scope as ScopeStorage;

class StorageMySqlScopeTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->pdo = $this->getMock('PDOStub');
	}


	public function tearDown()
	{
		unset($this->pdo);
	}


	public function testGetScopeFailsAndReturnsFalse()
	{
		$storage = new ScopeStorage($this->pdo, ['scopes' => 'scopes']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->with([':scope' => 'test'])->will($this->returnValue(false));

		$this->assertFalse($storage->get('test'));
	}


	public function testGetScopeSucceedsAndReturnsScopeEntity()
	{
		$storage = new ScopeStorage($this->pdo, ['scopes' => 'scopes']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->with([':scope' => 'test'])->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue(['scope' => 'test', 'name' => 'test', 'description' => 'test']));

		$scope = $storage->get('test');

		$this->assertEquals([
			'scope' => 'test',
			'name' => 'test',
			'description' => 'test'
		], $scope->getAttributes());
	}


	public function testGetScopePullsFromCacheOnSecondCall()
	{
		$storage = new ScopeStorage($this->pdo, ['scopes' => 'scopes']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->with([':scope' => 'test'])->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue(['scope' => 'test', 'name' => 'test', 'description' => 'test']));

		$storage->get('test');

		$this->assertEquals([
			'scope' => 'test',
			'name' => 'test',
			'description' => 'test'
		], $storage->get('test')->getAttributes());
	}


	public function testCreatingScopeSucceedsAndReturnsScopeEntity()
	{
		$storage = new ScopeStorage($this->pdo, ['scopes' => 'scopes']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));

		$this->assertEquals([
			'scope' => 'test',
			'name' => 'test',
			'description' => 'test'
		], $storage->create('test', 'test', 'test')->getAttributes());
	}


	public function testDeletingScope()
	{
		$storage = new ScopeStorage($this->pdo, ['scopes' => 'scopes']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));

		$storage->delete('test');
	}


}