<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.08.2018
 * Time: 11:09
 */

namespace EApp;

use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Traits\SingletonInstance;

/**
 * Class Host
 *
 * @package EApp
 * @method static Host getInstance()
 */
final class Host
{
	use SingletonInstance;

	/**
	 * @var string
	 */
	private $original_host = "";

	/**
	 * @var string
	 */
	private $host = "localhost";

	/**
	 * @var string
	 */
	private $application = "";

	/**
	 * @var string
	 */
	private $assets = "";

	/**
	 * @var string
	 */
	private $assets_path = "";

	/**
	 * @var bool
	 */
	private $ssl = false;

	/**
	 * @var int
	 */
	private $port = 80;

	/**
	 * @var string
	 */
	private $debug_mode = "production";

	/**
	 * @var string
	 */
	private $status = "unknown";

	/**
	 * @var bool|string
	 */
	private $file = false;

	/**
	 * @var string
	 */
	private $encoding = "UTF-8";

	private $hosts = [];
	private $www = [];
	private $alias = [];
	private $https = [];
	private $redirect = [];

	public function isCmd()
	{
		if( defined("CONSOLE_MODE") )
		{
			return CONSOLE_MODE;
		}

		if( function_exists("php_sapi_name") && php_sapi_name() == 'cli' )
		{
			return true;
		}

		return false;
	}

	public function isReferer()
	{
		if( $this->isCmd() )
		{
			return false;
		}

		if( isset($_SERVER['HTTP_REFERER'], $_SERVER["HTTP_HOST"]) )
		{
			$ref = ($this->isServerSsl() ? "https://" : "http://") . $this->originalHostName();
			if( $ref === $_SERVER['HTTP_REFERER'] || strpos($_SERVER['HTTP_REFERER'], $ref . "/") === 0 )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isSsl(): bool
	{
		return $this->ssl;
	}

	/**
	 * @return bool
	 */
	public function isServerSsl(): bool
	{
		return ( isset( $_SERVER['HTTPS'] ) && strtolower($_SERVER['HTTPS']) == 'on' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' );
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function getHostName(): string
	{
		return $this->host;
	}

	public function getOriginalHostName(): string
	{
		return $this->original_host;
	}

	public function getApplicationDir(): string
	{
		return $this->application;
	}

	public function getAssetsDir(): string
	{
		return $this->assets;
	}

	public function getAssetsPath(): string
	{
		return $this->assets_path;
	}

	public function getDebugMode(): string
	{
		return $this->debug_mode;
	}

	/**
	 * @return string
	 */
	public function getEncoding(): string
	{
		return $this->encoding;
	}

	/**
	 * @return string
	 */
	public function getFile(): string
	{
		return $this->file;
	}

	public function reload( $host = null, bool $push = true ): bool
	{
		if(defined("APP_HOST"))
		{
			throw new \InvalidArgumentException("You cannot reload host after defining the constants");
		}

		$this->loadHostFile();

		$port = 80;
		$ssl = false;

		if( is_string($host) && strlen($host) )
		{

			if( preg_match('/^https?:\/\//', $host, $m) )
			{
				$ssl = $m[0] === 'https://';
				$host = substr($host, strlen($m[0]));
			}
			if( preg_match('/:(\d{1,4})\/?$/', $host, $m) )
			{
				$port = (int) $m[1];
				$host = substr($host, 0, strlen($host) - strlen($m[0]));
			}
			if( !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) )
			{
				throw new \InvalidArgumentException("Invalid host name '{$host}'");
			}
		}
		else
		{
			$host = $this->originalHostName();

			if( defined("BASE_PROTOCOL") )
			{
				$ssl = BASE_PROTOCOL === "https";
			}
			else if( ! $this->isCmd() )
			{
				$ssl = $this->isServerSsl();
			}

			if( isset($_SERVER['SERVER_PORT']) && is_numeric($_SERVER['SERVER_PORT']) )
			{
				$port = (int) $_SERVER['SERVER_PORT'];
			}
		}

		$this->original_host = $host;
		$this->host = $host;
		$this->ssl = $ssl;
		$this->port = $port;

		// found host file

		$host = $this->host;

		// www
		if( strlen($host) > 4 && substr($host, 0, 4) === "www." )
		{
			$base_host = substr($host, 4);
			if( in_array($base_host, $this->www, true) )
			{
				$host = $base_host;
			}
		}

		// aliases
		if( isset($this->alias[$host]) )
		{
			$host = $this->alias[$host];
		}

		// https redirect
		if( ! $this->ssl && in_array($host, $this->https, true) )
		{
			return $this->redirect( "https://" . $this->original_host, $push );
		}

		// redirect
		if( isset($this->redirect[$host]) )
		{
			return $this->redirect( ($this->ssl ? "https": "http") . "://" . $this->redirect[$host], $push );
		}

		$pref = $this->ssl ? "https://" : "http://";
		$suffix = ':' . $this->port;

		if( $this->found(($pref . $host . $suffix), $host) ) return true;
		if( $this->found(($pref . $host), $host) ) return true;
		if( $this->found(($host . $suffix), $host) ) return true;
		if( $this->found($host, $host) ) return true;

		return false;
	}

	public function define()
	{
		if( ! defined("APP_HOST") )
		{
			define( "APP_HOST"          , $this->getHostName() );
			define( "APP_HOST_REFERER"  , $this->isReferer() );
			define( "ORIGINAL_HOST"     , $this->getOriginalHostName() );
			define( "APP_DIR"           , $this->getApplicationDir() );
			define( "ASSETS_DIR"        , $this->getAssetsDir() );
			define( "ASSETS_PATH"       , $this->getAssetsPath() );

			defined("BASE_ENCODING")    || define("BASE_ENCODING"  , $this->encoding );
			defined("BASE_PROTOCOL")    || define("BASE_PROTOCOL"  , $this->ssl ? 'https' : 'http');
			defined("CONSOLE_MODE")     || define("CONSOLE_MODE"   , $this->isCmd());
			defined("DEBUG_MODE")       || define("DEBUG_MODE"     , $this->getDebugMode());
		}
	}

	private function found(string $found, string $host): bool
	{
		if( array_key_exists($found, $this->hosts) )
		{
			$h = $this->hosts[$found];
			if( !isset($h["application"]) ) $h["application"] = "application";
			if( !isset($h["assets"]) ) $h["assets"] = "assets";

			$this->host = $host;
			$this->debug_mode = $m[3] ?? "production";

			$this->application = $this->separator($h["application"]);
			$this->assets = $this->separator($h["assets"]);
			$this->assets_path = ( isset($h["assets_path"]) && $h["assets_path"] != null ? rtrim($h["assets_path"], "/") : ("/" . $h["assets"]) ) . "/";
			$this->debug_mode = $h["debug_mode"] ?? "production";
			$this->encoding = $h["encoding"] ?? "UTF-8";
			$this->status = "load";

			return true;
		}

		return false;
	}

	private function originalHostName()
	{
		static $cmd_found = false;

		if( $this->isCmd() )
		{
			$host = empty($_SERVER['HTTP_HOST']) ? 'unknown.local' : $_SERVER['HTTP_HOST'];

			$argv = isset($_SERVER["argv"]) && is_array($_SERVER["argv"]) ? $_SERVER["argv"] : [];
			$i = array_search("--host", $argv );

			if( $i !== false && isset($argv[++$i]) && filter_var($argv[$i], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) )
			{
				$host = $argv[$i];
				$cmd_found = $host;

				array_splice($_SERVER["argv"], $i-1, 2);
			}
			else if($cmd_found)
			{
				return $cmd_found;
			}

			return $host;
		}
		else
		{
			return empty($_SERVER['HTTP_HOST']) ? 'localhost' : $_SERVER['HTTP_HOST'];
		}
	}

	private function loadHostFile()
	{
		if( $this->file )
		{
			return;
		}

		if( defined("HOST_FILE") )
		{
			$file = HOST_FILE;
		}
		else if( defined("BASE_DIR") )
		{
			$file = BASE_DIR . "hosts.php";
		}
		else
		{
			$file = getcwd() . DIRECTORY_SEPARATOR . "hosts.php";
		}

		if( !file_exists($file) )
		{
			throw new NotFoundException("Host file not found");
		}

		$this->file = $file;
		$this->tap($file, function($file) {
			include $file;

			if( isset($hosts) && is_array($hosts) ) $this->hosts = $hosts;
			if( isset($www) && is_array($www) ) $this->www = $www;
			if( isset($alias) && is_array($alias) ) $this->alias = $alias;
			if( isset($https) && is_array($https) ) $this->https = $https;
			if( isset($redirect) && is_array($redirect) ) $this->redirect = $redirect;
		});
	}

	private function tap( $arg, \Closure $call )
	{
		return $call($arg);
	}

	private function redirect( $location, bool $push )
	{
		$this->status = "redirect";

		if( $push && ! $this->isCmd() )
		{
			$location .= $_SERVER['REQUEST_URI'] ?? '/';

			if( headers_sent() )
			{
				$redirect  = "<html><head>";
				$redirect .= "<meta http-equiv=\"refresh\" content=\"1; url=" . $location . '" />';
				$redirect .= "<title>Redirecting</title>";
				$redirect .= "</head><body onload=\"location.replace('" . str_replace( "'", "\\'", $location );
				$redirect .= "' + document.location.hash)\">Redirecting you to " . $location . '</body></html>';

				echo $redirect;
			}
			else
			{
				header( 'Location: ' . $location );
			}
		}

		return true;
	}

	private function separator( $value )
	{
		$value = DIRECTORY_SEPARATOR === "/" ? $value : str_replace("/", DIRECTORY_SEPARATOR, $value);
		if( $value[0] !== DIRECTORY_SEPARATOR )
		{
			$dir = defined("BASE_DIR") ? BASE_DIR : getcwd() . DIRECTORY_SEPARATOR;
			$value = $dir . $value;
		}
		return $value . DIRECTORY_SEPARATOR;
	}
}