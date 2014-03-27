<?php namespace Dingo\OAuth2\Entity;

use BadMethodCallException;

abstract class Entity {

	/**
	 * Array of entity attributes.
	 * 
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * Dynamically get an attribute.
	 * 
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->attributes[$key];
	}

	/**
	 * Dynamically set an attribute.
	 * 
	 * @param  string  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->attributes[$this->snakeCase($key)] = $value;
	}

	/**
	 * Dynamically determine if an attribute is set.
	 * 
	 * @param  string  $key
	 * @return bool
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Dynamically get an attribute.
	 * 
	 * @param  string  $method
	 * @param  array  $parameters
	 * @return mixed
	 * @throws \BadMethodCallException
	 */
	public function __call($method, $parameters)
	{
		if (substr($method, 0, 3) == 'get')
		{
			$attribute = $this->snakeCase(substr($method, 3));

			return $this->attributes[$attribute];
		}

		throw new BadMethodCallException("Method [{$method}] not found.");
	}

	/**
	 * Convert a string to its snake case representation.
	 * 
	 * @param  string  $string
	 * @return string
	 */
	protected function snakeCase($string)
	{
		return preg_replace_callback('/(^|[a-z])([A-Z])/', function($matches)
		{
			return strtolower(empty($matches[1]) ? $matches[2] : "{$matches[1]}_{$matches[2]}");
		}, $string);
	}

	/**
	 * Dynamically convert an entity to its JSON representation.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return json_encode($this->attributes);
	}

}