<?php namespace Dingo\OAuth2\Entity;

class Scope extends Entity {

	public function __construct($id, $name = null, $description = null)
	{
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;
	}

}