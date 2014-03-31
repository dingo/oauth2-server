<?php

class EntityScopeableEntityTest extends PHPUnit_Framework_TestCase {


	public function testAttachingAndRetrievingScopes()
	{
		$entity = new ScopeableEntityStub;

		$entity->attachScopes([
			'foo' => 'bar'
		]);

		$this->assertTrue($entity->hasScope('foo'));
		$this->assertFalse($entity->hasScope('bar'));
		$this->assertEquals('bar', $entity->getScope('foo'));
	}


}