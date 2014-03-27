<?php namespace Dingo\OAuth2\Server;

use Dingo\OAuth2\Storage\Adapter;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Symfony\Component\HttpFoundation\Request;

class Resource {

	/**
	 * Storage adapter instance.
	 * 
	 * @var \Dingo\OAuth2\Storage\Adapter
	 */
	protected $storage;

	/**
	 * Symfony request instance.
	 * 
	 * @var \Symfony\Component\HttpFoundation\Request
	 */
	protected $request;

	/**
	 * Authenticated token entity.
	 * 
	 * @var \Dingo\OAuth2\Entity\Token
	 */
	protected $token;

	/**
	 * Create a new Dingo\OAuth2\Server\Resource instance.
	 * 
	 * @param  \Dingo\OAuth2\Storage\Adapter  $storage
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function __construct(Adapter $storage, Request $request = null)
	{
		$this->storage = $storage;
		$this->request = $request ?: Request::createFromGlobals();
	}

	/**
	 * Validate an access token.
	 * 
	 * @return bool
	 */
	public function validate()
	{
		if ( ! $token = $this->getAccessToken())
		{
			return false;
		}

		$this->token = $this->storage->get('token')->get($token);

		return $this->token instanceof TokenEntity;
	}

	/**
	 * Get the access token from either the header or request body.
	 * 
	 * @return bool|string
	 */
	public function getAccessToken()
	{
		if ($header = $this->request->headers->get('authorization'))
		{
			if (preg_match('/Bearer (\S+)/', $header, $matches))
			{
				list($header, $token) = $matches;

				return $token;
			}
		}
		elseif ($this->request->request->has('access_token'))
		{
			return $this->request->request->get('access_token');
		}

		return false;
	}

}