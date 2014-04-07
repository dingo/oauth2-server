# PHP OAuth 2.0 Server

A PHP OAuth 2.0 server implementation.

[![Build Status](https://travis-ci.org/dingo/oauth2-server.svg?branch=master)](https://travis-ci.org/dingo/oauth2-server)

## Installation

The package can be installed with Composer, either by modifying your `composer.json` directly or using the `composer require` command.

```
composer require dingo/oauth2-server:0.1.*
```

> Note that this package is still under development and has not been tagged as stable.

## Grant Types

All four OAuth 2.0 grant types detailed in the specification are implemented within this package.

- [Authorization Code](http://tools.ietf.org/html/rfc6749#section-1.3.1)
- [Implicit](http://tools.ietf.org/html/rfc6749#section-1.3.2)
- [Resource Owner Password Credentials](http://tools.ietf.org/html/rfc6749#section-1.3.3)
- [Client Credentials](http://tools.ietf.org/html/rfc6749#section-1.3.4)

## Storage Adapters

As of v0.1.0 the following storage adapters are available.

- `Dingo\OAuth2\Storage\PDOAdapter`
- `Dingo\OAuth2\Storage\RedisAdapter`

Using the [dingo/oauth2-server-laravel](https://github.com/dingo/oauth2-server-laravel) package you also have.

- `Dingo\OAuth2\Storage\FluentAdapter`

### MySQL Table Structure

The following is the table structure required for storage adapters which leverage MySQL. When developing your own storage adapters you can use this structure as a starting point.

```
CREATE TABLE IF NOT EXISTS `oauth_authorization_codes` (
  `code` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `client_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `redirect_uri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `expires` datetime NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `oauth_authorization_code_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `scope` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `oauth_clients` (
  `id` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `secret` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `oauth_client_endpoints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `uri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `oauth_scopes` (
  `scope` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `oauth_tokens` (
  `token` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('access','refresh') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'access',
  `client_id` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `expires` datetime NOT NULL,
  PRIMARY KEY (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `oauth_token_scopes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `scope` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `token` (`token`,`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
```

## Usage Guide

This guide is very brief and is framework agnostic. It's purpose is to simply demonstrate how the pieces come together and is not meant to be used as a real world implementation. As such there will be no mention of where each piece of code belongs.

Before we continue you should be aware of what the following terms mean.

| Term            | Meaning                                                    |
| --------------- | ---------------------------------------------------------- |
| **Client**      | An application, e.g., a PHP web application.               |
| **User**        | The applications user, also known as the *resource owner*. |

### Creating Clients

This package does not create or modify clients. The implementation for creating clients is in your own hands. When testing it's often easy to just insert a record into your MySQL table. For the rest of the guide it will be assumed that you have a client with the following details.

| Key           | Value     |
| ------------- | --------- |
| Client ID     | example   |
| Client Secret | topsecret |
| Client Name   | Example   |

Our client will also have a single associated endpoint: `http://localhost/example-client/authenticate`

This is the URI the Authorization Server will redirect the user to once they have authenticated and given the client permission.

### Authorization Server

The responsibilities of the Authorization Server are to authorize and issue access tokens to clients. Depending on the configuration the Authorization Server will also issue a refresh token which the client should store for when the access token expires.

To issue an access token the Authorization Server must be configured with the desired storage adapter and grant types.

```
$storage = new Dingo\OAuth2\Storage\PDOAdapter(new PDO('mysql:host=localhost;dbname=oauth', 'root'));

$server = new Dingo\OAuth2\Server\Authorization($storage);
```

We can now register the four different grant types depending on the projects requirements. For this guide we'll only be using the standard Authorization Code grant type.

```
$server->registerGrant(new Dingo\OAuth2\Grant\AuthorizationCode);
```

We'll now need a route that will handle the attempted authorization. This guide will assume this route is at `http://localhost/example-server/authorize`.

```
// If the user is not logged in we'll redirect them to the login form
// with the query string that was sent with the initial request.
if ( ! isset($_SESSION['user']))
{
	header("Location: /login?{$_SERVER['QUERY_STRING']}");
}
else
{
	try
	{
		$payload = $server->validateAuthorizationRequest();		
	}
	catch (Dingo\OAuth2\Exception\ClientException $exception)
	{
		echo $exception->getMessage();

		exit;
	}

	if (isset($_POST['submit']))
	{
		$response = $server->handleAuthorizationRequest($payload['client_id'], $payload['user_id'], $payload['redirect_uri'], $payload['scopes']);

		header("Location: {$server->makeRedirectUri($response)}");
	}
	else
	{
?>

<p><?php echo $payload['client']->getName(); ?> wants permission to:</p>

<table>
	<?php foreach($payload['scopes'] as $scope): ?>
	<tr>
		<td>
			<strong><?php echo $scope->getName(); ?></strong>
		</td>
		<td>
			<?php echo $scope->getDescription(); ?>
		</td>
	</tr>
	<?php endforeach; ?>
</table>

<form method="POST">
	<input type="submit" name="submit" value="Authorize">
	<input type="submit" name="cancel" value="Cancel">
</form>

<?php
	}
}
```

The client can now prompt a user to authorize via OAuth 2.0 by directing the user to a similar URI as follows (note that spacing has been added for readability).

```
http://localhost/example-server/authorize
	?response_type=code
	&client_id=example
	&redirect_uri=http%3A%2F%2Flocalhost%2Fexample-client%2Fauthenticate
```

If the Authorization Server detects that the user is not logged in they will be redirected to the login page and requested to login. Once logged in the user should be redirected back where they are prompted to authorize the client. If the user authorizes the client the Authorization Server will issue an authorization code which is sent back as part of the query string on the redirect URI that was provided. 

> Remember that the redirection URI provided must match a redirection URI that was registered for the client.

Now that the client has the authorization code it needs to request an access token from the Authorization Server using the code. We'll need a route that will handle the issuing of access tokens. This guide will assume the route is at `http://localhost/example-server/token`.

```
header('Content-Type: application/json');

echo json_encode($server->issueAccessToken());
```

The client can now request the access token by sending another request to the Authorization Server to a similar URI as follows (note that spacing has been added for readability).

```
http://localhost/example-server/token
	?grant_type=authorization_code
	&code=<authorization_code_returned_by_server>
	&client_id=example
	&client_secret=topsecret
```

The Authorization Server should respond with a JSON payload similar to the following.

```
{
	"access_token": "nkwCbxJ8EAEqEM11vCrKLd2TAqJLfCN21beMjVGK",
	"token_type": "Bearer",
	"expires": 1396795320,
	"expires_in": 3600,
	"refresh_token": "vnzKgulkldV1cnDeVh4y8KbAjDHCqvWBMnxTUqWa"
}
```

The client should save the refresh token and all subsequent requests to protected resources should include an `Authorization` header similar to the following.

```
Authorization: Bearer nkwCbxJ8EAEqEM11vCrKLd2TAqJLfCN21beMjVGK
```

### Resource Server

The responsibility of the Resource Server is to authenticate a request by validating the supplied access token.

```
$storage = new Dingo\OAuth2\Storage\PDOAdapter(new PDO('mysql:host=localhost;dbname=oauth', 'root'));

$server = new Dingo\OAuth2\Server\Resource($storage);
```

We can now validate that a request contains an access token that exists and has not expired.

```
try
{
	$server->validateRequest();
}
catch (Dingo\OAuth2\Exception\InvalidTokenException $exception)
{
	header('Content-Type: application/json', true, $exception->getStatusCode());

	echo $exception->getMessage();

	exit;
}
```