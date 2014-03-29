<?php

use Mockery as m;
use Dingo\OAuth2\Grant\Password;
use Symfony\Component\HttpFoundation\Request;

class GrantPasswordTest extends PHPUnit_Framework_TestCase {


	/**
	 * @expectedException \Dingo\OAuth2\Exception\ClientException
	 * @expectedExceptionMessage The user credentials failed to authenticate.
	 */
	public function testExecutingGrantFlowFailsWithInvalidUserCredentials()
	{
		$grant = (new Password)->setRequest(Request::create('test', 'GET', [
			'username' => 'foo', 'password' => 'bar'
		]));

		$grant->setAuthenticationCallback(function($username, $password)
		{
			$this->assertEquals('foo', $username);
			$this->assertEquals('bar', $password);

			return false;
		});

		$grant->execute();
	}


	protected function getStorageMock()
	{
		return m::mock('Dingo\OAuth2\Storage\Adapter');
	}


}