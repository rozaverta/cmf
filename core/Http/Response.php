<?php

namespace EApp\Http;

use EApp\Event\EventManager;
use EApp\Http\Events\ResponseFileEvent;
use EApp\Http\Events\ResponseJsonEvent;
use EApp\Support\Json;
use RuntimeException;
use EApp\Http\Collections\HeaderCollection;
use EApp\Http\Collections\ResponseCookieCollection;
use EApp\Http\Exceptions\ResponseAlreadySentException;
use EApp\Http\Events\ResponseRedirectEvent;
use EApp\Http\Events\ResponseSendEvent;
use EApp\Http\Exceptions\LockedResponseException;

/**
 * AbstractResponse
 */
class Response
{
	/**
	 * The default response HTTP status code
	 *
	 * @type int
	 */
	protected $default_status_code = 200;

	/**
	 * The HTTP version of the response
	 *
	 * @type string
	 */
	protected $protocol_version = '1.1';

	/**
	 * The response body
	 *
	 * @type string
	 */
	protected $body;

	/**
	 * HTTP response status
	 *
	 * @type Status
	 */
	protected $status;

	/**
	 * HTTP response headers
	 *
	 * @type HeaderCollection
	 */
	protected $headers;

	/**
	 * HTTP response cookies
	 *
	 * @type ResponseCookieCollection
	 */
	protected $cookies;

	/**
	 * Whether or not the response is "locked" from
	 * any further modification
	 *
	 * @type boolean
	 */
	protected $locked = false;

	/**
	 * Whether or not the response has been sent
	 *
	 * @type boolean
	 */
	protected $sent = false;

	/**
	 * Whether the response has been chunked or not
	 *
	 * @type boolean
	 */
	public $chunked = false;

	/**
	 * Constructor
	 *
	 * Create a new ResponsePrototype object with a dependency injected Headers instance
	 *
	 * @param string $body          The response body's content
	 * @param int $status_code      The status code
	 * @param array $headers        The response header "hash"
	 */
	public function __construct($body = '', $status_code = null, array $headers = [])
	{
		$status_code   = $status_code ?: $this->default_status_code;

		// SetTrait our body and code using our internal methods
		$this->setBody($body);
		$this->setCode($status_code);

		$this->headers = new HeaderCollection($headers);
		$this->cookies = new ResponseCookieCollection();
	}

	public function preventDefault()
	{
		if ($this->isLocked())
		{
			throw new LockedResponseException('Response is locked');
		}
		$this->sent = true;
		return $this;
	}

	/**
	 * Get the HTTP protocol version
	 *
	 * @return string
	 */
	public function getProtocolVersion()
	{
		return $this->protocol_version;
	}

	/**
	 * Set the HTTP protocol version
	 *
	 * @param string $protocol_version
	 * @return string|$this
	 */
	public function setProtocolVersion($protocol_version)
	{
		// Require that the response be unlocked before changing it
		$this->requireUnlocked();
		$this->protocol_version = (string) $protocol_version;
		return $this;
	}

	/**
	 * Set the response's body content
	 *
	 * @param string $body  The body content string
	 * @return $this
	 */
	public function setBody($body)
	{
		// Require that the response be unlocked before changing it
		$this->requireUnlocked();
		$this->body = (string) $body;
		return $this;
	}

	/**
	 * Get the response's body content
	 *
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * Returns the status object
	 *
	 * @return Status
	 */
	public function status()
	{
		return $this->status;
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
	 * Returns the cookies collection
	 *
	 * @return ResponseCookieCollection
	 */
	public function cookies()
	{
		return $this->cookies;
	}

	/**
	 * Set the HTTP response code
	 *
	 * @param int $code     The HTTP status code to send
	 * @return $this
	 */
	public function setCode( $code = null)
	{
		// Require that the response be unlocked before changing it
		$this->requireUnlocked();
		$this->status = new Status($code);
		return $this;
	}

	/**
	 * Get the HTTP response code
	 *
	 * @return int
	 */
	public function getCode()
	{
		return $this->status->getCode();
	}

	/**
	 * Prepend a string to the response's content body
	 *
	 * @param string $content   The string to prepend
	 * @return $this
	 */
	public function prepend($content)
	{
		// Require that the response be unlocked before changing it
		$this->requireUnlocked();
		$this->body = $content . $this->body;
		return $this;
	}

	/**
	 * Append a string to the response's content body
	 *
	 * @param string $content   The string to append
	 * @return $this
	 */
	public function append($content)
	{
		// Require that the response be unlocked before changing it
		$this->requireUnlocked();
		$this->body .= $content;
		return $this;
	}

	/**
	 * Require that the response is unlocked
	 *
	 * Throws an exception if the response is locked,
	 * preventing any methods from mutating the response
	 * when its locked
	 *
	 * @throws LockedResponseException  If the response is locked
	 * @return $this
	 */
	public function requireUnlocked()
	{
		if ($this->isLocked())
		{
			throw new LockedResponseException('Response is locked');
		}
		return $this;
	}

	/**
	 * Lock the response from further modification
	 *
	 * @return $this
	 */
	public function lock()
	{
		$this->locked = true;
		return $this;
	}

	/**
	 * Unlock the response from further modification
	 *
	 * @return $this
	 */
	public function unlock()
	{
		$this->locked = false;
		return $this;
	}

	/**
	 * Check if the response is locked
	 *
	 * @return boolean
	 */
	public function isLocked()
	{
		return $this->locked;
	}

	/**
	 * Generates an HTTP compatible status header line string
	 *
	 * Creates the string based off of the response's properties
	 *
	 * @return string
	 */
	protected function httpStatusLine()
	{
		return sprintf('HTTP/%s %s', $this->protocol_version, $this->status);
	}

	/**
	 * Send our HTTP headers
	 *
	 * @param boolean $cookies_also Whether or not to also send the cookies after sending the normal headers
	 * @param boolean $override     Whether or not to override the check if headers have already been sent
	 * @return $this
	 */
	public function sendHeaders($cookies_also = true, $override = false)
	{
		if (headers_sent() && !$override)
		{
			return $this;
		}

		// Send our HTTP status line
		header($this->httpStatusLine());

		// Iterate through our Headers data collection and send each header
		foreach ($this->headers as $key => $value)
		{
			header($key .': '. $value, false);
		}

		if($cookies_also)
		{
			$this->sendCookies($override);
		}

		return $this;
	}

	/**
	 * Send our HTTP response cookies
	 *
	 * @param boolean $override     Whether or not to override the check if headers have already been sent
	 * @return $this
	 */
	public function sendCookies($override = false)
	{
		if (headers_sent() && !$override)
		{
			return $this;
		}

		// Iterate through our Cookies data collection and set each cookie natively
		foreach($this->cookies as $cookie)
		{
			// Use the built-in PHP "setcookie" function
			setcookie(
				$cookie->getName(),
				$cookie->getValue(),
				$cookie->getExpire(),
				$cookie->getPath(),
				$cookie->getDomain(),
				$cookie->getSecure(),
				$cookie->getHttpOnly()
			);
		}

		return $this;
	}

	/**
	 * Send our body's contents
	 *
	 * @return $this
	 */
	public function sendBody()
	{
		echo (string) $this->body;
		return $this;
	}

	/**
	 * Send the response and lock it
	 *
	 * @param boolean $override             Whether or not to override the check if the response has already been sent
	 * @throws ResponseAlreadySentException If the response has already been sent
	 * @return $this
	 */
	public function send($override = false)
	{
		if ($this->sent && !$override)
		{
			throw new ResponseAlreadySentException('Response has already been sent');
		}

		// Call trigger
		$dispatcher = EventManager::dispatcher("onResponseSend");
		if( !$dispatcher->isRun() )
		{
			// fixed json
			$dispatcher->dispatch(new ResponseSendEvent($this));
		}

		// Send our response data
		$this->sendHeaders();
		$this->sendBody();

		// Lock the response from further modification
		$this->lock();

		// Mark as sent
		$this->sent = true;

		// If there running FPM, tell the process manager to finish the server request/response handling
		if (function_exists('fastcgi_finish_request'))
		{
			fastcgi_finish_request();
		}

		$dispatcher->complete();

		return $this;
	}

	/**
	 * Sends a file
	 *
	 * It should be noted that this method disables caching
	 * of the response by default, as dynamically created
	 * files responses are usually downloads of some type
	 * and rarely make sense to be HTTP cached
	 *
	 * Also, this method removes any data/content that is
	 * currently in the response body and replaces it with
	 * the file's data
	 *
	 * @param string $path The path of the file to send
	 * @param string $filename The file's name
	 * @param string $mime_type The MIME type of the file
	 * @param array $flags
	 * @return Response Thrown if the file could not be read
	 */
	public function file( $path, $filename = null, $mime_type = null, array $flags = [])
	{
		if ($this->sent)
		{
			throw new ResponseAlreadySentException('Response has already been sent');
		}

		if (null === $filename && in_array('attachment', $flags, true))
		{
			$filename = basename($path);
		}
		if (null === $mime_type && in_array('mime', $flags, true))
		{
			$mime_type = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
		}

		// Call trigger
		$dispatcher = EventManager::dispatcher("onResponseSend");
		$dispatcher->dispatch(new ResponseFileEvent($this, $path, $filename, $mime_type));

		$this->setBody('');

		in_array('nocache', $flags, true) && $this->noCache();
		$mime_type && $this->header('Content-type', $mime_type);
		$filename && $this->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

		// If the response is to be chunked, then the content length must not be sent
		// see: https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.4
		if (false === $this->chunked)
		{
			$this->header('Content-length', filesize($path));
		}

		// Send our response data
		$this->sendHeaders();

		$bytes_read = readfile($path);

		if(false === $bytes_read)
		{
			throw new RuntimeException('The file could not be read');
		}

		$this->sendBody();

		// Lock the response from further modification
		$this->lock();

		// Mark as sent
		$this->sent = true;

		// If there running FPM, tell the process manager to finish the server request/response handling
		if (function_exists('fastcgi_finish_request'))
		{
			fastcgi_finish_request();
		}

		$dispatcher->complete();

		return $this;
	}

	/**
	 * Sends an object as json or jsonp by providing the padding prefix
	 *
	 * It should be noted that this method disables caching
	 * of the response by default, as json responses are usually
	 * dynamic and rarely make sense to be HTTP cached
	 *
	 * Also, this method removes any data/content that is
	 * currently in the response body and replaces it with
	 * the passed json encoded object
	 *
	 * @param mixed $object         The data to encode as JSON
	 * @param string $jsonp_prefix  The name of the JSON-P function prefix
	 * @return Response
	 */
	public function json($object, $jsonp_prefix = null)
	{
		if ($this->sent)
		{
			throw new ResponseAlreadySentException('Response has already been sent');
		}

		$this->requireUnlocked();

		// Call trigger
		$dispatcher = EventManager::dispatcher("onResponseSend");
		$event = new ResponseJsonEvent($this, $object, $jsonp_prefix);
		$dispatcher->dispatch($event);

		$this->setBody('');
		$this->noCache();

		$json = Json::stringify($event->getParam("json"));

		if (null !== $jsonp_prefix)
		{
			// Should ideally be application/json-p once adopted
			$this->header('Content-Type', 'text/javascript');
			$this->setBody("$jsonp_prefix($json);");
		}
		else
		{
			$this->header('Content-Type', 'application/json');
			$this->setBody($json);
		}

		$this->send();

		return $this;
	}

	/**
	 * Check if the response has been sent
	 *
	 * @return boolean
	 */
	public function isSent()
	{
		return $this->sent;
	}

	/**
	 * Sets a response header
	 *
	 * @param string $key       The name or string of the HTTP response header
	 * @param mixed $value      The value to set the header with
	 * @return $this
	 */
	public function header($key, $value = null)
	{
		if( is_null($value) )
		{
			$pos = strpos($key, ":");
			if( ! $pos )
			{
				return $this;
			}

			$value = substr($key, $pos + 1);
			$key = substr($key, 0, $pos);
		}
		$this->headers->set($key, $value);
		return $this;
	}

	/**
	 * Sets a response cookie
	 *
	 * @param string $key           The name of the cookie
	 * @param string $value         The value to set the cookie with
	 * @param int $expiry           The time that the cookie should expire
	 * @param string $path          The path of which to restrict the cookie
	 * @param string $domain        The domain of which to restrict the cookie
	 * @param boolean $secure       Flag of whether the cookie should only be sent over a HTTPS connection
	 * @param boolean $httponly     Flag of whether the cookie should only be accessible over the HTTP protocol
	 * @return $this
	 */
	public function cookie(
		$key,
		$value = '',
		$expiry = null,
		$path = '/',
		$domain = null,
		$secure = false,
		$httponly = false
	) {
		if (null === $expiry) {
			$expiry = time() + (3600 * 24 * 30);
		}

		$this->cookies->set(
			$key,
			new ResponseCookie($key, $value, $expiry, $path, $domain, $secure, $httponly)
		);

		return $this;
	}

	/**
	 * Tell the browser not to cache the response
	 *
	 * @return $this
	 */
	public function noCache()
	{
		$this->header('Pragma', 'no-cache');
		$this->header('Cache-Control', 'no-store, no-cache');
		return $this;
	}

	/**
	 * Tell the browser the cache time
	 *
	 * @param int | \DateTime $time
	 * @param string $e_tag
	 * @return $this
	 */
	public function cache( $time = null, $e_tag = null )
	{
		if( $time instanceof \DateTime )
		{
			$time = $time->getTimestamp() - time();
		}
		else if( null === $time )
		{
			$time = 3600*24*7; // default cache 7 day
		}
		else
		{
			$time = (int) $time;
		}

		// Invalid timeout
		if( $time < 1 )
		{
			return $this;
		}

		$this->header('Cache-Control', 'public, max-age=' . $time);
		if( null !== $e_tag )
		{
			$this->header("ETag", $e_tag);
		}

		return $this;
	}

	/**
	 * Redirects the request to another URL
	 *
	 * @param string $url   The URL to redirect to
	 * @param boolean $permanent     The HTTP status code to use for redirection
	 * @param boolean $refresh Use Refresh: 0; header method
	 * @return $this
	 */
	public function redirect($url, $permanent = false, $refresh = false)
	{
		if(strpos($url, '://') === false)
		{
			$host = APP_HOST;
			if( strlen($url) && $url[0] !== "/" )
			{
				$host .= "/";
			}

			$url = BASE_PROTOCOL . '://' . $host . $url;
		}

		EventManager::dispatch(new ResponseRedirectEvent($this, $url, $permanent, $refresh));

		if( headers_sent() )
		{
			$body  = '<html><head>';
			$body .= '<meta http-equiv="refresh" content="1; url=' . $url . '" />';
			$body .= '<title>Redirecting</title>';
			$body .= '</head><body onload="location.replace(\'' . str_replace( "'", "\\'", $url );
			$body .= '\' + document.location.hash)">Redirecting you to ' . $url . '</body></html>';
			$this->setBody($body);
		}
		else
		{
			$this->setCode($permanent ? 301 : 302);
			if( $refresh )
			{
				$this->header("Refresh", "0; URL=" . $url);
			}
			else
			{
				$this->header('Location', $url);
			}
		}

		$this->lock();

		return $this;
	}

	/**
	 * Enable response chunking
	 *
	 * @link https://github.com/klein/klein.php/wiki/Response-Chunking
	 * @link http://bit.ly/hg3gHb
	 * @param string $str   An optional string to send as a response "chunk"
	 * @return Response
	 */
	public function chunk($str = null)
	{
		if (false === $this->chunked)
		{
			$this->chunked = true;
			$this->header('Transfer-encoding', 'chunked');
			flush();
		}

		if (($body_length = strlen($this->body)) > 0)
		{
			printf("%x\r\n", $body_length);
			$this->sendBody();
			$this->setBody('');
			echo "\r\n";
			flush();
		}

		if (null !== $str)
		{
			printf("%x\r\n", strlen($str));
			echo "$str\r\n";
			flush();
		}

		return $this;
	}

	/**
	 * Dump a variable
	 *
	 * @param mixed $obj    The variable to dump
	 * @return Response
	 */
	public function dump($obj)
	{
		if (is_array($obj) || is_object($obj))
		{
			$obj = print_r($obj, true);
		}

		$this->append('<pre>' .  htmlentities($obj, ENT_QUOTES) . "</pre><br />\n");

		return $this;
	}
}