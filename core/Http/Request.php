<?php

namespace EApp\Http;

use EApp\Http\Collections\HeaderCollection;
use EApp\Http\Collections\ServerCollection;
use EApp\Support\Collection;
use EApp\Support\Json;

class Request
{
	/**
	 * Unique identifier for the request
	 *
	 * @type string
	 */
	protected $id;

	/**
	 * IP address
	 *
	 * @type string
	 */
	protected $ip_address;

	/**
	 * GET (query) parameters
	 *
	 * @type Collection
	 */
	protected $params_get;

	/**
	 * POST parameters
	 *
	 * @type Collection
	 */
	protected $params_post;

	/**
	 * Named parameters
	 *
	 * @type Collection
	 */
	protected $params_named;

	/**
	 * Client cookie data
	 *
	 * @type Collection
	 */
	protected $cookies;

	/**
	 * Server created attributes
	 *
	 * @type ServerCollection
	 */
	protected $server;

	/**
	 * HTTP request headers
	 *
	 * @type HeaderCollection
	 */
	protected $headers;

	/**
	 * Uploaded temporary files
	 *
	 * @type Collection
	 */
	protected $files;

	/**
	 * The request body
	 *
	 * @type string
	 */
	protected $body;

	/**
	 * Named data loaded from json
	 *
	 * @var bool
	 */
	protected $from_json = null;

	/**
	 * Constructor
	 *
	 * Create a new Request object and define all of its request data
	 *
	 * @param array  $params_get
	 * @param array  $params_post
	 * @param array  $cookies
	 * @param array  $server
	 * @param array  $files
	 * @param string $body
	 */
	public function __construct(
		array $params_get = [],
		array $params_post = [],
		array $cookies = [],
		array $server = [],
		array $files = [],
		$body = null
	) {
		// Assignment city...
		$this->params_get   = new Collection($params_get);
		$this->params_post  = new Collection($params_post);
		$this->cookies      = new Collection($cookies);
		$this->server       = new ServerCollection($server);
		$this->headers      = new HeaderCollection($this->server->getHeaders());
		$this->files        = new Collection($files);
		$this->body         = $body ? (string) $body : null;

		// Non-injected assignments
		$this->params_named = new Collection();
	}

	/**
	 * Create a new request object using the built-in "superglobals"
	 *
	 * @link http://php.net/manual/en/language.variables.superglobals.php
	 * @return Request
	 */
	public static function createFromGlobals()
	{
		// Create and return a new instance of this
		return new static(
			$_GET,
			$_POST,
			$_COOKIE,
			$_SERVER,
			$_FILES,
			null // Let our content getter take care of the "body"
		);
	}

	/**
	 * Gets a unique ID for the request
	 *
	 * Generates one on the first call
	 *
	 * @param boolean $hash     Whether or not to hash the ID on creation
	 * @return string
	 */
	public function id($hash = true)
	{
		if (null === $this->id)
		{
			$this->id = uniqid();
			if ($hash)
			{
				$this->id = sha1($this->id);
			}
		}

		return $this->id;
	}

	/**
	 * Returns the GET parameters collection
	 *
	 * @return \EApp\Support\Collection
	 */
	public function paramsGet()
	{
		return $this->params_get;
	}

	/**
	 * Returns the POST parameters collection
	 *
	 * @return \EApp\Support\Collection
	 */
	public function paramsPost()
	{
		return $this->params_post;
	}

	/**
	 * Returns the named parameters collection
	 *
	 * @return \EApp\Support\Collection
	 */
	public function paramsNamed()
	{
		return $this->params_named;
	}

	/**
	 * Returns the cookies collection
	 *
	 * @return \EApp\Support\Collection
	 */
	public function cookies()
	{
		return $this->cookies;
	}

	/**
	 * Returns the server collection
	 *
	 * @return \EApp\Support\Collection
	 */
	public function server()
	{
		return $this->server;
	}

	/**
	 * Returns the headers collection
	 *
	 * @return HeaderCollection
	 */
	public function headers()
	{
		return $this->headers;
	}

	/**
	 * Returns the files collection
	 *
	 * @return \EApp\Support\Collection
	 */
	public function files()
	{
		return $this->files;
	}

	/**
	 * Gets the request body
	 *
	 * @return string
	 */
	public function body()
	{
		// Only get it once
		if (null === $this->body)
		{
			$this->body = @ file_get_contents('php://input');
		}

		return $this->body;
	}

	/**
	 * Returns all parameters (GET, POST, named, and cookies) that match the mask
	 *
	 * Takes an optional mask param that contains the names of any params
	 * you'd like this method to exclude in the returned array
	 *
	 * @see \EApp\Support\Collection::all()
	 * @param array $mask               The parameter mask array
	 * @param boolean $fill_with_nulls  Whether or not to fill the returned array
	 *  with null values to match the given mask
	 * @return array
	 */
	public function params($mask = null, $fill_with_nulls = false)
	{
		// Merge our params in the get, post, cookies, named order
		$params = array_merge(
			$this->params_get->toArray(),
			$this->params_post->toArray(),
			$this->cookies->toArray(),
			$this->params_named->toArray() // Add our named params last
		);

		if( is_array($mask) )
		{
			$params = (new Collection($params))->mask($mask, $fill_with_nulls)->toArray();
		}

		return $params;
	}

	/**
	 * Return a request parameter, or $default if it doesn't exist
	 *
	 * @param string $key       The name of the parameter to return
	 * @param mixed $default    The default value of the parameter if it contains no value
	 * @return mixed
	 */
	public function param($key, $default = null)
	{
		switch($this->paramIs($key))
		{
			case 'get'    : return $this->params_get->get($key);
			case 'post'   : return $this->params_post->get($key);
			case 'cookie' : return $this->cookies->get($key);
			case 'name'   : return $this->params_named->get($key);
		}

		return $default;
	}

	public function paramIs($key)
	{
		if( isset($this->params_get[$key]) ) return 'get';
		if( isset($this->params_post[$key]) ) return 'post';
		if( isset($this->cookies[$key]) ) return 'cookie';
		if( isset($this->params_named[$key]) ) return 'name';
		return false;
	}

	public function get($key, $default = null)
	{
		return $this->params_get->getOr($key, $default);
	}

	public function post($key, $default = null)
	{
		return $this->params_post->getOr($key, $default);
	}

	public function cookie($key, $default = null)
	{
		return $this->cookies->getOr($key, $default);
	}

	public function name($key, $default = null)
	{
		return $this->params_named->getOr($key, $default);
	}

	/**
	 * Content loaded from json
	 *
	 * @return bool
	 */
	public function isJson()
	{
		return $this->from_json === true;
	}

	/**
	 * Load json content from body data
	 *
	 * @param bool $check_header
	 * @param bool $override
	 * @return bool
	 */
	public function readJson($check_header = true, $override = false)
	{
		if( !is_null($this->from_json) && !$override )
		{
			return $this->from_json;
		}

		// set false json reader
		$this->from_json = false;

		if( $check_header )
		{
			$type = $this->server->getIs("CONTENT_TYPE") ? $this->server->get("CONTENT_TYPE") : $this->server->getOr("HTTP_ACCEPT", '');
			if( ! preg_match('/(?:application|text)\/json(?:$|;| )/', $type) )
			{
				return false;
			}
		}

		$body = $this->body();
		if( strlen($body) && ( $body[0] === "{" || $body[0] === "[" ) )
		{
			$this->params_post = new Collection( Json::parse($body, true) );
			$this->body = '';
			$this->from_json = true;
		}

		return $this->from_json;
	}

	/**
	 * Is the request secure?
	 *
	 * @return boolean
	 */
	public function isSecure()
	{
		return ($this->server->get('HTTPS') == true);
	}

	/**
	 * Gets the request IP address
	 *
	 * @return string
	 */
	public function ip()
	{
		if( is_null($this->ip_address) )
		{
			$remote = isset( $this->server["REMOTE_ADDR"] );

			if( $remote && isset( $this->server["HTTP_CLIENT_IP"] ) )
			{
				$this->ip_address = $this->server["HTTP_CLIENT_IP"];
			}
			else if( $remote )
			{
				$this->ip_address = $_SERVER["REMOTE_ADDR"];
			}
			else if ( isset( $this->server["HTTP_CLIENT_IP"] ) )
			{
				$this->ip_address = $this->server["HTTP_CLIENT_IP"];
			}
			else if( isset( $this->server["HTTP_X_FORWARDED_FOR"] ) )
			{
				$this->ip_address = $this->server["HTTP_X_FORWARDED_FOR"];
			}
			else
			{
				$this->ip_address = "0.0.0.0";
			}

			if( strpos( $this->ip_address, ',' ) !== false )
			{
				$this->ip_address = end( explode( ',', $this->ip_address ) );
			}

			if( ! $this->ip_address )
			{
				$this->ip_address = "0.0.0.0";
			}
		}

		return $this->ip_address;
	}

	/**
	 * Gets the request user agent
	 *
	 * @return string
	 */
	public function userAgent()
	{
		return $this->headers->get('USER_AGENT');
	}

	/**
	 * Gets the request URI
	 *
	 * @return string
	 */
	public function uri()
	{
		return $this->server->getOr('REQUEST_URI', '/');
	}

	/**
	 * Gets the request referer
	 *
	 * @param bool $valid_host
	 * @param string $valid_string
	 * @return string
	 */
	public function referer( $valid_host = false, $valid_string = '' )
	{
		$ref = $this->server->getOr('HTTP_REFERER', '');
		if( $ref ) {

			if($valid_host) {
				$host = BASE_PROTOCOL . "://" . APP_HOST;
				$len = strlen($host);

				if( substr($ref, 0, $len) !== $host ) {
					return '';
				}

				if( strlen($ref) > $len ) {
					$end = $ref[$len];
					if( $end !== "/" && $end !== ":" ) {
						return '';
					}
				}
			}

			if($valid_string && strpos($ref, $valid_string) === false) {
				return '';
			}
		}

		return $ref;
	}

	/**
	 * GetTrait the request's pathname
	 *
	 * @return string
	 */
	public function pathname()
	{
		$uri = $this->uri();

		// Strip the query string from the URI
		$uri = strstr($uri, '?', true) ?: $uri;

		return $uri;
	}

	/**
	 * Gets the request method, or checks it against $is
	 *
	 * <code>
	 * // POST request example
	 * $request->method() // returns 'POST'
	 * $request->method('post') // returns true
	 * $request->method('get') // returns false
	 * </code>
	 *
	 * @param string $is				The method to check the current request method against
	 * @param boolean $allow_override	Whether or not to allow HTTP method overriding via header or params
	 * @return string|boolean
	 */
	public function method($is = null, $allow_override = true)
	{
		$method = $this->server->getOr('REQUEST_METHOD', 'GET');

		// Override
		if($allow_override && $method === 'POST')
		{
			$override = $this->server->getIs('X_HTTP_METHOD_OVERRIDE') ? $this->server->get('X_HTTP_METHOD_OVERRIDE') : null;
			if( ! $override )
			{
				$method = $this->param('_method', $method);
			}
			else
			{
				// For legacy servers, override the HTTP method with the X-HTTP-Method-Override header or _method parameter
				$method = $override;
			}

			$method = strtoupper($method);
		}

		// We're doing a check
		if (null !== $is)
		{
			return strcasecmp($method, $is) === 0;
		}

		return $method;
	}

	/**
	 * Adds to or modifies the current query string
	 *
	 * @param string $key   The name of the query param
	 * @param mixed $value  The value of the query param
	 * @return string
	 */
	public function query($key, $value = null)
	{
		$query = [];

		parse_str(
			$this->server()->get('QUERY_STRING'),
			$query
		);

		if(is_array($key))
		{
			$query = array_merge($query, $key);
		}
		else
		{
			$query[$key] = $value;
		}

		$request_uri = $this->uri();

		if(strpos($request_uri, '?') !== false)
		{
			$request_uri = strstr($request_uri, '?', true);
		}

		return $request_uri . (!empty($query) ? '?' . http_build_query($query) : null);
	}
}