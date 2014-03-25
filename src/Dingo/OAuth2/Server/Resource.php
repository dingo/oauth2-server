<?php namespace Dingo\OAuth2\Server;

use Dingo\OAuth2\Storage\Adapter;
use Symfony\Component\HttpFoundation\Request;

class Resource {

	protected $storage;

	public function __construct(Adapter $storage, Request $request = null)
	{
		$this->storage = $storage;
		$this->request = $request ?: Request::createFromGlobals();
	}

	public function validate()
	{
		$token = $this->getAccessToken();
	}

	public function getAccessToken()
	{
		if ($header = $this->request->headers->get('authorization'))
		{
			var_dump($header);

			exit;
		}
	}

	public function getStorage()
	{
		return $this->storage;
	}

}