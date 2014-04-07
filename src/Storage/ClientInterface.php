<?php namespace Dingo\OAuth2\Storage;

interface ClientInterface {

	/**
	 * Get a client from storage. Should return false if client was not found.
	 * If no redirection URI is provided then another query should be run
	 * to fetch the default URI from the related table.
	 * 
	 * Example MySQL query when secret and redirection URI are provided:
	 * 
	 * SELECT oauth_clients.*, oauth_client_endpoints.uri AS redirect_uri
	 * FROM oauth_clients
	 * INNER JOIN oauth_client_endpoints ON oauth_clients.id = oauth_client_endpoints.client_id
	 * WHERE oauth_clients.id = :id
	 * AND oauth_clients.secret = :secret
	 * AND oauth_client_endpoints.uri = :redirectUri
	 * 
	 * Example MySQL query when only the secret is provided:
	 * 
	 * SELECT oauth_clients.*
	 * FROM oauth_clients
	 * WHERE oauth_clients.id = :id
	 * AND oauth_clients.secret = :secret
	 * 
	 * Example MySQL query when only the redirection URI is provided:
	 * 
	 * SELECT oauth_clients.*, oauth_client_endpoints.uri AS redirect_uri
	 * FROM oauth_clients
	 * INNER JOIN oauth_client_endpoints ON oauth_clients.id = oauth_client_endpoints.client_id
	 * WHERE oauth_clients.id = :id
	 * AND oauth_client_endpoints.uri = :redirectUri
	 * 
	 * Example MySQL query when no secret or redirection URI is provided:
	 * 
	 * SELECT oauth_clients.*
	 * FROM oauth_clients
	 * WHERE oauth_clients.id = :id
	 * 
	 * @param  string  $id
	 * @param  string  $secret
	 * @param  string  $redirectUri
	 * @return \Dingo\OAuth2\Entity\Client|false
	 */
	public function get($id, $secret = null, $redirectUri = null);

}