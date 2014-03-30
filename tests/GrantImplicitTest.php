<?php

use Mockery as m;
use Dingo\OAuth2\Grant\Implicit;
use Symfony\Component\HttpFoundation\Request;

class GrantImplicitTest extends PHPUnit_Framework_TestCase {


	public function testCorrectGrantIdentifier()
	{
		$grant = new Implicit;

		$this->assertEquals('implicit', $grant->getGrantIdentifier());
	}


	public function testCorrectResponseType()
	{
		$grant = new Implicit;
		
		$this->assertEquals('token', $grant->getResponseType());
	}


	protected function getStorageMock()
	{
		return m::mock('Dingo\OAuth2\Storage\Adapter');
	}


}