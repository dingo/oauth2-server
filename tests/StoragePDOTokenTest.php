<?php

use Dingo\OAuth2\Entity\Token as TokenEntity;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;
use Dingo\OAuth2\Storage\PDO\Token as TokenStorage;

class StoragePDOTokenTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->pdo = $this->getMock('PDOStub');
	}


	public function tearDown()
	{
		unset($this->pdo);
	}


	public function testCreateTokenEntityFailsAndReturnsFalse()
	{
		$storage = new TokenStorage($this->pdo, ['tokens' => 'tokens']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->with([
			':token' => 'test',
			':type' => 'access',
			':client_id' => 'test',
			':user_id' => 1,
			':expires' => '1991-01-31 12:00:00'
		])->will($this->returnValue(false));

		$this->assertFalse($storage->create('test', 'access', 'test', 1, strtotime('31 January 1991 12:00:00')));
	}


	public function testCreateTokenEntitySucceedsAndReturnsTokenEntity()
	{
		$storage = new TokenStorage($this->pdo, ['tokens' => 'tokens']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->with([
			':token' => 'test',
			':type' => 'access',
			':client_id' => 'test',
			':user_id' => 1,
			':expires' => '1991-01-31 12:00:00'
		])->will($this->returnValue(true));

		$token = $storage->create('test', 'access', 'test', 1, strtotime('31 January 1991 12:00:00'));

		$this->assertEquals([
			'token' => 'test',
			'type' => 'access',
			'client_id' => 'test',
			'user_id' => 1,
			'expires' => strtotime('1991-01-31 12:00:00'),
			'scopes' => []
		], $token->getAttributes());
	}


	public function testAssociatingScopesToToken()
	{
		$storage = new TokenStorage($this->pdo, ['token_scopes' => 'token_scopes']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->exactly(2))->method('execute')->will($this->returnValue(true));

		$storage->associateScopes('test', [
			'foo' => new ScopeEntity('foo', 'foo', 'foo'),
			'bar' => new ScopeEntity('bar', 'bar', 'bar')
		]);
	}


	public function testGetTokenEntityFailsAndReturnsFalse()
	{
		$storage = new TokenStorage($this->pdo, ['tokens' => 'tokens']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(false));

		$this->assertFalse($storage->get('test'));
	}


	public function testGetTokenEntitySucceedsAndReturnsTokenEntity()
	{
		$storage = new TokenStorage($this->pdo, ['tokens' => 'tokens']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'token' => 'test',
			'type' => 'access',
			'client_id' => 'test',
			'user_id' => 1,
			'expires' => '1991-01-31 12:00:00'
		]));

		$token = $storage->get('test');

		$this->assertEquals([
			'token' => 'test',
			'type' => 'access',
			'client_id' => 'test',
			'user_id' => 1,
			'scopes' => [],
			'expires' => strtotime('1991-01-31 12:00:00')
		], $token->getAttributes());
	}


	public function testGetTokenWithScopesFailsAndReturnsFalse()
	{
		$storage = new TokenStorage($this->pdo, ['tokens' => 'tokens']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(false));

		$this->assertFalse($storage->getWithScopes('test'));
	}


	public function testGetTokenWithScopesSucceedsAndReturnsTokenEntityWithAttachedScopes()
	{
		$storage = new TokenStorage($this->pdo, [
			'tokens' => 'tokens',
			'scopes' => 'scopes',
			'token_scopes' => 'token_scopes'
		]);

		$this->pdo->expects($this->at(0))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'token' => 'test',
			'type' => 'access',
			'client_id' => 'test',
			'user_id' => 1,
			'expires' => '1991-01-31 12:00:00'
		]));

		$this->pdo->expects($this->at(1))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetchAll')->will($this->returnValue([
			[
				'scope' => 'foo',
				'name' => 'foo',
				'description' => 'foo'
			],
			[
				'scope' => 'bar',
				'name' => 'bar',
				'description' => 'bar'
			]
		]));

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
			'expires' => strtotime('1991-01-31 12:00:00')
		], $token->getAttributes());
	}


	public function testDeleteToken()
	{
		$storage = new TokenStorage($this->pdo, [
			'tokens' => 'tokens',
			'token_scopes' => 'token_scopes'
		]);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));

		$storage->delete('test');
	}


}