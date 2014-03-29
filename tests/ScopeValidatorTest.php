<?php

use Mockery as m;
use Dingo\OAuth2\ScopeValidator;
use Dingo\OAuth2\Entity\Scope as ScopeEntity;
use Symfony\Component\HttpFoundation\Request;

class ScopeValidatorTest extends PHPUnit_Framework_TestCase {


	public function tearDown()
	{
		m::close();
	}


	public function testValidatingRequestWithNoScopesReturnsEmptyArray()
	{
		$validator = new ScopeValidator(Request::create('test', 'POST'), $this->getStorageMock());

		$this->assertEmpty($validator->validate());
	}


	public function testValidatingRequestWithScopesReturnsArrayWithScopeEntity()
	{
		$validator = new ScopeValidator(Request::create('test', 'POST', ['scope' => 'test']), $storage = $this->getStorageMock());

		$storage->shouldReceive('get')->once()->with('test')->andReturn(new ScopeEntity('test', 'test', 'test'));

		$scopes = $validator->validate();

		$this->assertNotEmpty($scopes);
		$this->assertEquals('test', $scopes['test']->getScope());
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 */
	public function testValidatingRequestWithScopesThrowsExceptionForInvalidScope()
	{
		$validator = new ScopeValidator(Request::create('test', 'POST', ['scope' => 'test']), $storage = $this->getStorageMock());

		$storage->shouldReceive('get')->once()->with('test')->andReturn(false);

		$validator->validate();
	}


	public function testValidatingRequestWithNoScopesUsesDefaultScopes()
	{
		$validator = new ScopeValidator(Request::create('test', 'POST'), $storage = $this->getStorageMock());

		$validator->setDefaultScope(['foo', 'bar']);

		$storage->shouldReceive('get')->once()->with('foo')->andReturn(new ScopeEntity('foo', 'foo', 'foo'));
		$storage->shouldReceive('get')->once()->with('bar')->andReturn(new ScopeEntity('bar', 'bar', 'bar'));

		$scopes = $validator->validate();

		$this->assertArrayHasKey('foo', $scopes);
		$this->assertArrayHasKey('bar', $scopes);
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 */
	public function testValidatingRequestWithNoScopesWhenScopesAreRequiredThrowsException()
	{
		$validator = new ScopeValidator(Request::create('test', 'POST'), $this->getStorageMock());

		$validator->requireScope();

		$validator->validate();
	}


	public function testValidatingRequestWithScopesSeparatedByComma()
	{
		$validator = new ScopeValidator(Request::create('test', 'POST', ['scope' => 'foo,bar']), $storage = $this->getStorageMock());

		$validator->setScopeDelimiter(',');

		$storage->shouldReceive('get')->once()->with('foo')->andReturn(new ScopeEntity('foo', 'foo', 'foo'));
		$storage->shouldReceive('get')->once()->with('bar')->andReturn(new ScopeEntity('bar', 'bar', 'bar'));

		$scopes = $validator->validate();

		$this->assertArrayHasKey('foo', $scopes);
		$this->assertArrayHasKey('bar', $scopes);
	}


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 */
	public function testValidatingRequestWithScopesComparesAgainstOriginalScopesAndThrowsExceptionForUnknownScope()
	{
		$validator = new ScopeValidator(Request::create('test', 'POST', ['scope' => 'foo']), $storage = $this->getStorageMock());

		$validator->validate(['bar' => [], 'baz' => []]);
	}


	public function testValidatingRequestWithNoScopesUsesOriginalScopes()
	{
		$validator = new ScopeValidator(Request::create('test', 'POST'), $storage = $this->getStorageMock());

		$storage->shouldReceive('get')->once()->with('foo')->andReturn(new ScopeEntity('foo', 'foo', 'foo'));
		$storage->shouldReceive('get')->once()->with('bar')->andReturn(new ScopeEntity('bar', 'bar', 'bar'));

		$scopes = $validator->validate(['foo' => [], 'bar' => []]);

		$this->assertArrayHasKey('foo', $scopes);
		$this->assertArrayHasKey('bar', $scopes);
	}


	public function getStorageMock()
	{
		return m::mock('Dingo\OAuth2\Storage\ScopeInterface');
	}


}