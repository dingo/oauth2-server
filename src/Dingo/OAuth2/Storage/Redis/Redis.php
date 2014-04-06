<?php namespace Dingo\OAuth2\Storage\Redis;

use Closure;
use Predis\Client as PredisClient;

abstract class Redis {

	/**
	 * Predis client instance.
	 * 
	 * @var Redis\Client
	 */
	protected $redis;

	/**
	 * Prefix used for Redis keys.
	 * 
	 * @var string
	 */
	protected $prefix;

	/**
	 * Array of tables.
	 * 
	 * @var array
	 */
	protected $tables;

	/**
	 * Array of cached key/value pairs.
	 * 
	 * @var array
	 */
	protected $cache = [];

	/**
	 * Create a new Dingo\OAuth2\Storage\Redis\Redis instance.
	 * 
	 * @param  \Predis\Client  $redis
	 * @param  string  $prefix
	 * @param  array  $tables
	 * @return void
	 */
	public function __construct(PredisClient $redis, array $tables)
	{
		$this->redis = $redis;
		$this->tables = $tables;
	}

	/**
	 * Get a value from the Redis store.
	 * 
	 * @param  string  $key
	 * @param  string  $table
	 * @return mixed
	 */
	public function getValue($key, $table)
	{
		$key = $this->prefix($key, $table);

		if (isset($this->cache[$key]))
		{
			return $this->cache[$key];
		}

		if ( ! $value = $this->redis->get($key))
		{
			return false;
		}

		return $this->cache[$key] = (is_string($value) and $decoded = json_decode($value, true)) ? $decoded : $value;
	}

	/**
	 * Set a value in the Redis store.
	 * 
	 * @param  string  $key
	 * @param  string  $table
	 * @param  mixed  $value
	 * @return bool
	 */
	public function setValue($key, $table, $value)
	{
		$key = $this->prefix($key, $table);

		$this->cache[$key] = $value;

		return $this->redis->set($key, $this->prepareValue($value));
	}

	/**
	 * Push a value onto a list.
	 * 
	 * @param  string  $key
	 * @param  string  $table
	 * @param  mixed  $value
	 * @return int
	 */
	public function pushList($key, $table, $value)
	{
		$key = $this->prefix($key, $table);

		if ( ! isset($this->cache[$key]))
		{
			$this->cache[$key] = [];
		}

		array_push($this->cache[$key], $value);

		return $this->redis->rpush($key, $this->prepareValue($value));
	}

	/**
	 * Get a list from the Redis store.
	 * 
	 * @param  string  $key
	 * @param  string  $table
	 * @return array
	 */
	public function getList($key, $table)
	{
		$key = $this->prefix($key, $table);

		if (isset($this->cache[$key]))
		{
			return $this->cache[$key];
		}

		$list = $this->redis->lrange($key, 0, -1);

		// We'll spin through each item on the array and attempt to decode
		// any JSON so that we get the proper array representations.
		return $this->cache[$key] = array_map(function($item)
		{
			if (is_string($item) and $decoded = json_decode($item, true))
			{
				return $decoded;
			}

			return $item;
		}, $list);
	}

	/**
	 * Delete a key from the Redis store.
	 * 
	 * @param  string  $key
	 * @param  string  $table
	 * @return int
	 */
	public function deleteKey($key, $table)
	{
		$key = $this->prefix($key, $table);

		if (isset($this->cache[$key]))
		{
			unset($this->cache[$key]);
		}

		return $this->redis->del($key);
	}

	/**
	 * Get a matching set member by using a callback to run the
	 * comparison. If the callback returns a non-null response
	 * then that response is assumed to be a match.
	 * 
	 * @param  string  $key
	 * @param  string  $table
	 * @param  \Closure  $callback
	 * @return mixed
	 */
	public function getMatchingMember($key, $table, Closure $callback)
	{
		$key = $this->prefix($key, $table);

		foreach ($this->redis->smembers($key) as $member)
		{
			if ($response = $callback($member))
			{
				return $response;
			}
		}
	}

	/**
	 * Prepare a value for storage in Redis.
	 * 
	 * @param  mixed  $value
	 * @return string
	 */
	protected function prepareValue($value)
	{
		// If the value is an array it will be encoded and we'll store the
		// JSON representation.
		if (is_array($value))
		{
			$value = json_encode($value);
		}

		return $value;
	}

	/**
	 * Prefix a key with its table.
	 * 
	 * @param  string  $key
	 * @param  string  $table
	 * @return string
	 */
	protected function prefix($key, $table)
	{
		$table = str_replace('_', ':', $table);

		return "{$table}:{$key}";
	}

}