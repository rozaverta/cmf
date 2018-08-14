<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.08.2018
 * Time: 23:01
 */

namespace EApp\System\ConsoleCommands\Api\Host;

use EApp\Support\Interfaces\Arrayable;
use EApp\Support\Str;

class Host implements Arrayable
{
	/**
	 * @var string
	 */
	private $host;

	private $application = "application";

	private $assets = "assets";

	private $assets_path = "";

	private $www = false;

	private $ssl = false;

	private $aliases = [];

	private $redirect = [];

	private $invoker = [];

	private $debug_mode = null;

	public function __construct(string $host)
	{
		$filter_host = self::filter($host, false, $match);
		if( ! $filter_host )
		{
			throw new \InvalidArgumentException("Invalid host name {$host}");
		}

		$this->host = $filter_host;
	}

	/**
	 * @return null
	 */
	public function getDebugMode()
	{
		return $this->debug_mode;
	}

	/**
	 * @param string $debug_mode
	 * @return $this
	 */
	public function setDebugMode( string $debug_mode )
	{
		if( strlen($debug_mode) )
		{
			$this->debug_mode = $debug_mode;
			$this->invoke("debug mode", $debug_mode );
		}
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHostName(): string
	{
		return $this->host;
	}

	/**
	 * @return string
	 */
	public function getApplication(): string
	{
		return $this->application;
	}

	/**
	 * @param string $application
	 * @return $this
	 */
	public function setApplication( string $application )
	{
		$this->application = $application;
		$this->invoke("application", $application);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAssets(): string
	{
		return $this->assets;
	}

	/**
	 * @param string $assets
	 * @return $this
	 */
	public function setAssets( string $assets )
	{
		$this->assets = $assets;
		$this->invoke("assets", $assets);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getAssetsPath(): string
	{
		return $this->assets_path;
	}

	/**
	 * @param string $assets_path
	 * @return $this
	 */
	public function setAssetsPath( string $assets_path )
	{
		$this->assets_path = $assets_path;
		$this->invoke("assets path", $assets_path);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function hasAssetsPath(): bool
	{
		return strlen($this->assets_path) > 0;
	}

	/**
	 * @return bool
	 */
	public function isWww(): bool
	{
		return $this->www;
	}

	/**
	 * Use or not WWW prefix for domain
	 *
	 * @param bool $www
	 * @return $this
	 */
	public function setWww( bool $www = true )
	{
		$this->www = $www;
		$this->invoke("www", $www);
		return $this;
	}

	/**
	 * @param string $host
	 * @param bool $redirect
	 * @return $this
	 */
	public function addAlias( string $host, bool $redirect = false )
	{
		$host = self::filter($host, true);

		if( $host && $host !== $this->host )
		{
			$www = ("www." . $host) === $this->getHostName();
			if( $www )
			{
				$this->setWww( ! $redirect );
			}

			if($redirect)
			{
				// remove host from alias
				if( !$www )
				{
					$index = array_search($host, $this->aliases);
					if( $index !== false )
					{
						array_splice($this->aliases, $index, 1);
						$this->invoke("remove alias", $host);
					}
				}

				// add redirect
				if( ! in_array($host, $this->redirect, true) )
				{
					$this->redirect[] = $host;
					$this->invoke("redirect", $host);
				}
			}
			else if( ! $www && ! in_array($host, $this->aliases, true) )
			{
				// remove host from redirect
				$index = array_search($host, $this->redirect);
				if( $index !== false )
				{
					array_splice($this->redirect, $index, 1);
					$this->invoke("remove redirect", $host);
				}

				// add alias
				$this->aliases[] = $host;
				$this->invoke("alias", $host);
			}
		}

		return $this;
	}

	/**
	 * @param string $host
	 * @return bool
	 */
	public function inAlias( string $host ): bool
	{
		$host = self::filter($host, true);
		return $host && in_array($host, $this->aliases, true);
	}

	/**
	 * @return array
	 */
	public function getAliases(): array
	{
		return $this->aliases;
	}

	/**
	 * @param string $host
	 * @return $this
	 */
	public function removeAlias( string $host )
	{
		$host = self::filter($host, true);
		if($host)
		{
			$index = array_search($host, $this->aliases);
			if( $index !== false )
			{
				array_splice($this->aliases, $index, 1);
				$this->invoke("remove alias", $host);
			}

			$index = array_search($host, $this->redirect);
			if( $index !== false )
			{
				array_splice($this->redirect, $index, 1);
				$this->invoke("remove redirect", $host);
			}
		}
		return $this;
	}

	/**
	 * @param string $host
	 * @return bool
	 */
	public function inRedirect( string $host ): bool
	{
		$host = self::filter($host, true);
		return $host && in_array($host, $this->redirect, true);
	}

	/**
	 * @return array
	 */
	public function getRedirect(): array
	{
		return $this->redirect;
	}

	/**
	 * @return bool
	 */
	public function isSsl(): bool
	{
		return $this->ssl;
	}

	/**
	 * @param bool $ssl
	 * @return $this
	 */
	public function setSsl( bool $ssl = true )
	{
		$this->ssl = $ssl;
		$this->invoke("ssl", $ssl);
		return $this;
	}

	/**
	 * @param \Closure $invoke
	 * @return $this
	 */
	public function addInvoker( \Closure $invoke )
	{
		$this->invoker = $invoke;
		return $this;
	}

	public static function filter( string $host, bool $filter_strip = false, & $match = null )
	{
		$test = Str::lower(trim($host));

		$match = [
			'ssl'  => false,
			'port' => 80
		];

		// remove ^ http:// or https://
		if( preg_match('|^(https?)://(.*?)$|', $test, $m) )
		{
			$match['ssl'] = $m[1] === "https";
			$test = $m[2];
		}

		// remove port
		if( preg_match('|^(.*?):(\d{1,4})\/?$|', $test, $m) )
		{
			$match['port'] = (int) $m[2];
			$test = $m[1];
		}

		if( strlen($test) && filter_var($test, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) )
		{
			return $filter_strip ? $test : $host;
		}

		return false;
	}

	private function invoke( $type, $value = null )
	{
		if( count($this->invoker) )
		{
			foreach($this->invoker as $invoke)
			{
				$invoke($type, $value);
			}
		}
	}

	public function toArray()
	{
		$row = [
			"host" => $this->getHostName(),
			"application" => $this->getApplication(),
			"assets" => $this->getAssets(),
			"www" => $this->isWww(),
			"ssl" => $this->isSsl()
		];

		if( $this->assets_path && $this->assets_path !== $this->assets )
		{
			$row["assets_path"] = $this->assets_path;
		}

		if( $this->debug_mode )
		{
			$row["debug_mode"] = $this->debug_mode;
		}

		return $row;
	}
}