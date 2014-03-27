<?php namespace Dingo\OAuth2\Storage;

interface TokenInterface {

	/**
	 * Insert a token into storage. The expires time is a UNIX timestamp and should
	 * be saved in a compatible format. When it's pulled from storage is should
	 * be returned as a UNIX timestamp.
	 * 
	 * Example MySQL query:
	 * 
	 * INSERT INTO oauth_tokens (token, type, client_id, user_id, expires) 
	 * VALUES (:token, :type, :client_id, :user_id, :expires)
	 * 
	 * @param  string  $token
	 * @param  string  $type
	 * @param  string  $clientId
	 * @param  mixed  $userId
	 * @param  int  $expires
	 * @return \Dingo\OAuth2\Entity\Token
	 */
	public function create($token, $type, $clientId, $userId, $expires);

	/**
	 * Get an access token from storage. The expires time MUST be returned as
	 * a UNIX timestamp. This method should also retrieve the associated
	 * scopes of the token and attach them to the token entity.
	 * 
	 * Example MySQL query:
	 * 
	 * SELECT * FROM oauth_tokens WHERE token = :token
	 * 
	 * Example MySQL query to fetch associated scopes:
	 * 
	 * SELECT oauth_scopes.* FROM oauth_scopes
	 * LEFT JOIN oauth_token_scopes ON oauth_scopes.scope = oauth_token_scopes.scope
	 * WHERE oauth_token_scopes.token = :token
	 * 
	 * @param  string  $token
	 * @return \Dingo\OAuth2\Entity\Token|bool
	 */
	public function get($token);

}