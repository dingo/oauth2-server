<?php namespace Dingo\OAuth2\Storage;

interface AuthorizationCodeInterface {

	/**
	 * Insert an authorization code into storage. The expires time is a UNIX timestamp
	 * and should be saved in a compatible format. When it's pulled from storage it
	 * should be returned as a UNIX timestamp.
	 * 
	 * Example MySQL query:
	 * 
	 * INSERT INTO oauth_authorization_codes (code, client_id, user_id, redirect_uri, expires) 
	 * VALUES (:code, :client_id, :user_id, :redirect_uri, :expires)
	 * 
	 * @param  string  $code
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  string  $redirectUri
	 * @param  int  $expires
	 * @return \Dingo\OAuth2\Entity\AuthorizationCode
	 */
	public function create($code, $clientId, $userId, $redirectUri, $expires);

	/**
	 * Associate scopes with an authorization code. The array will contain
	 * instances of \Dingo\OAuth2\Entity\Scope.
	 * 
	 * Example MySQL query to associate a scope with the code:
	 * 
	 * INSERT INTO oauth_authorization_code_scopes (code, scope)
	 * VALUES (:code, :scope)
	 * 
	 * @param  string  $code
	 * @param  array  $scopes
	 * @return void
	 */
	public function associateScopes($code, array $scopes);

	/**
	 * Get a code from storage. The expires time MUST be returned as a UNIX
	 * timestamp. This method should also retrieve the associated scopes
	 * of the authorization code and attach them to the  authorization
	 * code entity.
	 * 
	 * Example MySQL query:
	 * 
	 * SELECT * FROM oauth_authorization_codes WHERE code = :code
	 * 
	 * Example MySQL query to fetch associated scopes:
	 * 
	 * SELECT oauth_scopes.* FROM oauth_scopes
	 * LEFT JOIN oauth_authorization_code_scopes ON oauth_scopes.scope = oauth_authorization_code_scopes.scope
	 * WHERE oauth_authorization_code_scopes.code = :code
	 * 
	 * @param  string  $code
	 * @return \Dingo\OAuth2\Entity\AuthorizationCode|bool
	 */
	public function get($code);

	/**
	 * Delete an authorization code from storage. This method should also delete
	 * any associated scopes.
	 * 
	 * Example MySQL query to delete an authorization code and scopes:
	 * 
	 * DELETE FROM oauth_authorization_codes WHERE scope = :scope
	 * 
	 * DELETE FROM oauth_authorization_code_scopes WHERE scope = :scope
	 * 
	 * @param  string  $code
	 * @return void
	 */
	public function delete($code);

}