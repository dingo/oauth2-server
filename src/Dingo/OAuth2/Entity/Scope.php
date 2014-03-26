<?php namespace Dingo\OAuth2\Entity;

class Scope extends Entity {

	public function __construct($scope, $name = null, $description = null)
	{
		$this->scope = $scope;
		$this->name = $name;
		$this->description = $description;
	}

}