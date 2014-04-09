<?php

use Dingo\OAuth2\Entity\Scope as ScopeEntity;
use Dingo\OAuth2\Entity\AuthorizationCode as AuthorizationCodeEntity;
use Dingo\OAuth2\Storage\MySql\AuthorizationCode as AuthorizationCodeStorage;

class StorageMySqlAuthorizationCodeTest extends PHPUnit_Framework_TestCase {


	public function setUp()
	{
		$this->pdo = $this->getMock('PDOStub');
	}


	public function tearDown()
	{
		unset($this->pdo);
	}


	public function testCreateAuthorizationCodeEntitySucceedsAndReturnsAuthorizationCodeEntity()
	{
		$storage = new AuthorizationCodeStorage($this->pdo, ['authorization_codes' => 'authorization_codes']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->with([
			':code' => 'test',
			':client_id' => 'test',
			':user_id' => 1,
			':redirect_uri' => 'test',
			':expires' => '1991-01-31 12:00:00'
		])->will($this->returnValue(true));

		$token = $storage->create('test', 'test', 1, 'test', strtotime('31 January 1991 12:00:00'));

		$this->assertEquals([
			'code' => 'test',
			'client_id' => 'test',
			'user_id' => 1,
			'redirect_uri' => 'test',
			'expires' => strtotime('1991-01-31 12:00:00'),
			'scopes' => []
		], $token->getAttributes());
	}


	public function testAssociatingScopesToAuthorizationCode()
	{
		$storage = new AuthorizationCodeStorage($this->pdo, ['authorization_code_scopes' => 'authorization_code_scopes']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->exactly(2))->method('execute')->will($this->returnValue(true));

		$storage->associateScopes('test', [
			'foo' => new ScopeEntity('foo', 'foo', 'foo'),
			'bar' => new ScopeEntity('bar', 'bar', 'bar')
		]);
	}


	public function testGetAuthorizationCodeEntityFailsAndReturnsFalse()
	{
		$storage = new AuthorizationCodeStorage($this->pdo, ['authorization_codes' => 'authorization_codes']);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(false));

		$this->assertFalse($storage->get('test'));
	}


	public function testGetAuthorizationCodeEntitySucceedsAndReturnsAuthorizationCodeEntity()
	{
		$storage = new AuthorizationCodeStorage($this->pdo, [
			'authorization_codes' => 'authorization_codes',
			'scopes' => 'scopes',
			'authorization_code_scopes' => 'authorization_code_scopes'
		]);

		$this->pdo->expects($this->at(0))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'code' => 'test',
			'client_id' => 'test',
			'user_id' => 1,
			'redirect_uri' => 'test',
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
			'expires' => strtotime('1991-01-31 12:00:00')
		], $code->getAttributes());
	}


	public function testGetAuthorizationCodePullsFromCacheOnSecondCall()
	{
		$storage = new AuthorizationCodeStorage($this->pdo, [
			'authorization_codes' => 'authorization_codes',
			'scopes' => 'scopes',
			'authorization_code_scopes' => 'authorization_code_scopes'
		]);

		$this->pdo->expects($this->at(0))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));
		$statement->expects($this->once())->method('fetch')->will($this->returnValue([
			'code' => 'test',
			'client_id' => 'test',
			'user_id' => 1,
			'redirect_uri' => 'test',
			'expires' => '1991-01-31 12:00:00'
		]));

		$this->pdo->expects($this->at(1))->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(false));

		$storage->get('test');

		$this->assertEquals([
			'code' => 'test',
			'client_id' => 'test',
			'user_id' => 1,
			'redirect_uri' => 'test',
			'scopes' => [],
			'expires' => strtotime('1991-01-31 12:00:00')
		], $storage->get('test')->getAttributes());
	}


	public function testDeleteAuthorizationCode()
	{
		$storage = new AuthorizationCodeStorage($this->pdo, [
			'authorization_codes' => 'authorization_codes',
			'authorization_code_scopes' => 'authorization_code_scopes'
		]);

		$this->pdo->expects($this->once())->method('prepare')->will($this->returnValue($statement = $this->getMock('PDOStatement')));
		$statement->expects($this->once())->method('execute')->will($this->returnValue(true));

		$storage->delete('test');
	}


}