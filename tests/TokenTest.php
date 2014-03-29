<?php

class TokenTest extends PHPUnit_Framework_TestCase {


	public function testRandomTokenGenerated()
	{
		$this->assertEquals(40, strlen(Dingo\OAuth2\Token::make()));
		$this->assertEquals(20, strlen(Dingo\OAuth2\Token::make(20)));
	}


}