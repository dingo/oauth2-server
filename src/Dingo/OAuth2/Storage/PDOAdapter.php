<?php namespace Dingo\OAuth2\Storage;

use PDO;
use Dingo\OAuth2\Storage\PDO\Scope;
use Dingo\OAuth2\Storage\PDO\Token;
use Dingo\OAuth2\Storage\PDO\Client;

class PdoAdapter extends Adapter {

	protected $connection;

	protected $tables = [
		'clients'                   => 'oauth_clients',
		'client_endpoints'          => 'oauth_client_endpoints',
		'tokens'                    => 'oauth_tokens',
		'token_scopes'              => 'oauth_token_scopes',
		'authorization_code'        => 'oauth_authorization_codes',
		'authorization_code_scopes' => 'oauth_authorization_code_scopes',
		'scopes'                    => 'oauth_scopes'
	];

	public function __construct(PDO $connection, array $tables = [])
	{
		$this->connection = $connection;
		$this->tables = array_merge($this->tables, $tables);
	}

	public function createClientStorage()
	{
		return new Client($this->connection, $this->tables);
	}

	public function createTokenStorage()
	{
		return new Token($this->connection, $this->tables);
	}

	public function createAuthorizationCodeStorage()
	{

	}

	public function createScopeStorage()
	{
		return new Scope($this->connection, $this->tables);
	}

}