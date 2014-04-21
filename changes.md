## Changes

### 0.2.1 (April 21st, 2014)

- Changing test bootstrapping.

### 0.2.0 (April 11th, 2014)

- Fixed bug where refresh token entity JSON was inserted into `oauth_token_scopes` table instead of the actual token.
- Clients can be marked as trusted for automatic authorization.

### 0.1.3 (April 10th, 2014)

- Throw more meaningful exception by including a generic error type.
- Can now create and delete clients and scopes.
- User defined authorized callback is now fired once an access token has been issued.
- A "redirect_uri" parameter is now optional as per the spec when using the Authorization Code and Implicit grant types.

### 0.1.2 (April 8th, 2014)

- Switch to PSR-4 autoloading.
- Fixed bug where resources could not be protected by a scope.
- Implemented default scopes for every resource.

### 0.1.1 (April 8th, 2014)

- Allow tables to be set on storage adapters during runtime.

### 0.1.0 (April 7th, 2014)

- Initial development release.
