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
	 * @return \Dingo\OAuth2\Entity\Client|bool
	 */
	public function get($id, $secret = null, $redirectUri = null);

	/**
	 * Create a client and associated redirection URIs.
	 * 
	 * Example MySQL query to create client:
	 * 
	 * INSERT INTO oauth_clients (id, secret, name, trusted) 
	 * VALUES (:id, :secret, :name, :trusted)
	 * 
	 * Example MySQL query to create associated redirection URIs:
	 * 
	 * INSERT INTO oauth_client_endpoints (client_id, uri, is_default) 
	 * VALUES (:client_id, :uri, :is_default)
	 * 
	 * @param  string  $id
	 * @param  string  $secret
	 * @param  string  $name
	 * @param  array  $redirectUris
	 * @param  bool  $trusted
	 * @return \Dingo\OAuth2\Entity\Client
	 */
	public function create($id, $secret, $name, array $redirectUris, $trusted = false);

	/**
	 * Delete a client and associated redirection URIs.
	 * 
	 * Example MySQL query to delete client:
	 * 
	 * DELETE FROM oauth_clients
	 * WHERE oauth_clients.id = :id
	 * 
	 * Example MySQL query to delete associated redirection URIs:
	 * 
	 * DELETE FROM oauth_client_endpoints
	 * WHERE oauth_client_endpoints.client_id = :id
	 * 
	 * @param  string  $id
	 * @return void
	 */
	public function delete($id);

}