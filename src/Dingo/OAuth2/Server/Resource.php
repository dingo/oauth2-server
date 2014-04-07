<?php namespace Dingo\OAuth2\Server;

use Dingo\OAuth2\Storage\Adapter;
use Dingo\OAuth2\Entity\Token as TokenEntity;
use Symfony\Component\HttpFoundation\Request;
use Dingo\OAuth2\Exception\InvalidTokenException;

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
	 * @return \Dingo\OAuth2\Entity\Token
	 * @throws \Dingo\OAuth2\Exception\InvalidTokenException
	 */
	public function validateRequest()
	{
		if ( ! $token = $this->getAccessToken())
		{
			throw new InvalidTokenException('Access token was not supplied.', 401);
		}

		if ( ! $this->token = $this->storage->get('token')->get($token))
		{
			throw new InvalidTokenException('Invalid access token.', 401);
		}

		if ($this->tokenHasExpired($this->token))
		{
			$this->storage->get('token')->delete($token);

			throw new InvalidTokenException('Access token has expired.', 401);
		}

		return $this->token;
	}

	/**
	 * Determine if a token has expired.
	 * 
	 * @param  \Dingo\OAuth2\Entity\Token  $token
	 * @return bool
	 */
	protected function tokenHasExpired(TokenEntity $token)
	{
		return $token->getExpires() < time();
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
		elseif ($this->request->get('access_token'))
		{
			return $this->request->get('access_token');
		}

		return false;
	}

}