<?php namespace Dingo\OAuth2\Grant;

interface GrantInterface {

	/**
	 * Get the grant identifier.
	 * 
	 * @return string
	 */
	public function getGrantIdentifier();

}