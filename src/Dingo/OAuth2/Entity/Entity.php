<?php namespace Dingo\OAuth2\Entity;

abstract class Entity {

	protected $attributes = [];

	public function __get($key)
	{
		return $this->attributes[$key];
	}

	public function __set($key, $value)
	{
		$this->attributes[$this->snakeCase($key)] = $value;
	}

	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	public function __call($method, $parameters)
	{
		if (substr($method, 0, 3) == 'get')
		{
			$attribute = $this->snakeCase(substr($method, 3));

			return $this->attributes[$attribute];
		}
	}

	protected function snakeCase($string)
	{
		return preg_replace_callback('/(^|[a-z])([A-Z])/', function($matches)
		{
			return strtolower(empty($matches[1]) ? $matches[2] : "{$matches[1]}_{$matches[2]}");
		}, $string);
	}

	public function __toString()
	{
		return json_encode($this->attributes);
	}

}