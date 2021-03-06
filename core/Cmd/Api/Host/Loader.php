<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.08.2018
 * Time: 23:01
 */

namespace EApp\Cmd\Api\Host;

use EApp\App;
use EApp\Support\Collection;
use EApp\Traits\SingletonInstanceTrait;

class Loader
{
	use SingletonInstanceTrait;

	/**
	 * @var Host[]
	 */
	private $hosts = [];

	public function load( string $file, bool $ignore_modify = false )
	{
		$this->hosts = [];

		if( file_exists($file) )
		{
			if(! $ignore_modify)
			{
				$text = @ file_get_contents($file);
				if( !$text )
				{
					throw new \InvalidArgumentException("Cannot ready the host file");
				}

				$end_of = strpos($text, '/** @md5:');
				if( $end_of === false )
				{
					throw new \InvalidArgumentException("The host file was not automatically generated");
				}

				if( substr($text, $end_of + 9, 32) !== md5(substr($text, 0, $end_of)))
				{
					throw new \InvalidArgumentException("The host file was changed manually");
				}
			}

			$this->tap($file, function(string $file) {

				/** @noinspection PhpIncludeInspection */
				include $file;

				$this->loadData(
					$hosts ?? [],
					$alias ?? [],
					$redirect ?? []
				);
			});

			return true;
		}

		return false;
	}

	// -- hosts --------------------------------------------------------------------------------------------------------

	/**
	 * @return Collection <Host>
	 */
	public function getHosts(): Collection
	{
		return new Collection($this->hosts);
	}

	/**
	 * Add new host item
	 *
	 * @param Host $host
	 * @return $this
	 */
	public function addHost( Host $host )
	{
		$host_name = $host->getHostName();
		if( $this->offsetExists($host_name) ) throw new \InvalidArgumentException("Duplicate host {$host_name}");

		foreach( $this->hosts as $h )
		{
			// check WWW duplicate name
			if($h->isWww() && ("www." . $h->getHostName()) === $host_name)
				throw new \InvalidArgumentException("Duplicate host {$host_name}");

			// remove aliases and redirect from other hosts
			if($h->inAlias($host_name) || $h->inRedirect($host_name))
				$h->removeAlias($host_name);
		}

		// check duplicate names
		foreach( $host->getAliases() as $alias ) $this->throwAlias($alias, $host_name);
		foreach( $host->getRedirect() as $redirect ) $this->throwAlias($redirect, $host_name);

		$host->addInvoker(function($type, $value) use($host_name) {
			switch($type)
			{
				case "alias":
				case "redirect": $this->throwAlias($value, $host_name); break;
			}
		});

		return $this;
	}

	/**
	 * Get host item
	 *
	 * @param string $host domain name
	 * @return Host|null
	 */
	public function getHost(string $host)
	{
		$host = Host::filter($host);
		return $this->offsetExists($host) ? $this->hosts[$host] : null;
	}

	/**
	 * Check host exists
	 *
	 * @param string $host domain name
	 * @return bool
	 */
	public function hasHost(string $host): bool
	{
		$host = Host::filter($host);
		return $this->offsetExists($host);
	}

	/**
	 * Remove host item
	 *
	 * @param string $host domain name
	 * @return $this
	 */
	public function removeHost(string $host)
	{
		$host = Host::filter($host);
		if( $this->offsetExists($host) ) unset( $this->hosts[$host] );
		return $this;
	}

	// --

	public function inWww(string $host): bool
	{
		return $this->in($host, static function(Host $h) { return $h->isWww(); });
	}

	public function inSsl(string $host): bool
	{
		return $this->in($host, static function(Host $h) { return $h->isSsl(); });
	}

	// --

	/**
	 * @param string $host
	 * @param string $compare
	 * @return bool
	 */
	public function hasAlias( string $host, string $compare = "" ): bool
	{
		return $this->has($host, $compare, static function(Host $h, $host) { return $h->inAlias($host); });
	}

	/**
	 * @param string $host
	 * @param string $compare
	 * @return bool
	 */
	public function hasRedirect( string $host, string $compare = "" ): bool
	{
		return $this->has($host, $compare, static function(Host $h, $host) { return $h->inRedirect($host); });
	}

	// --

	public function createFileContent()
	{
		$php = App::PhpExport();

		$hosts = [];
		$alias = [];
		$redirect = [];

		// fill hosts array
		foreach( $this->hosts as $host_name => $host )
		{
			$hosts[] = $host->toArray();
			foreach($host->getAliases() as $alias) $alias[$alias] = $host_name;
			foreach($host->getRedirect() as $alias) $redirect[$alias] = $host_name;
		}

		$hosts    = $php->assoc( $hosts );
		$alias    = $php->assoc( $alias );
		$redirect = $php->assoc( $redirect );

		$text = /** @lang text */ <<<PHP
<?php

/**
 * Be careful!
 *
 * This file was generated automatically.
 * Any changes will be deleted at the next update!
 * ----------------------------------------------- */

if( ! defined("ELS_CMS") )
{
	exit;
}

\$hosts = {$hosts};
\$alias = {$alias};
\$redirect = {$redirect};

PHP;

		return $text . "/** @md5:" . md5($text) . " */";
	}

	private function offsetExists( string $host )
	{
		return $host && array_key_exists($host, $this->hosts);
	}

	private function loadData( array $hosts, array $alias, array $redirect )
	{
		$all_alias = [];

		foreach($alias as $a => $host)
		{
			$host = Host::filter($host);
			if( $host )
			{
				if( ! isset($all_alias[$host]) ) $all_alias[$host] = [];
				$all_alias[$host][] = [$a, false];
			}
		}

		foreach($redirect as $a => $host)
		{
			$host = Host::filter($host);
			if( $host )
			{
				if( ! isset($all_alias[$host]) ) $all_alias[$host] = [];
				$all_alias[$host][] = [$a, true];
			}
		}

		foreach($hosts as $host_name => $prop)
		{
			$host = new Host($host_name);
			$host_name = $host->getHostName();

			isset($prop["application"]) && $host->setApplication($prop["application"]);
			isset($prop["assets"]) && $host->setAssets($prop["assets"]);
			isset($prop["assets_path"]) && $host->setAssetsPath($prop["assets_path"]);
			isset($prop["debug_mode"]) && $host->setDebugMode($prop["debug_mode"]);
			isset($prop["www"]) && $prop["www"] && $host->setWww();
			isset($prop["ssl"]) && $prop["ssl"] && $host->setSsl();

			if( isset($all_alias[$host_name]) )
				foreach($all_alias[$host_name] as $a)
					$host->addAlias(... $a);

			$this->addHost($host);
		}
	}

	private function throwAlias($alias, $host)
	{
		foreach($this->hosts as $host_name => $h)
		{
			if(
				$host_name === $alias ||
				$h->isWww() && ("www." . $host_name) === $alias ||
				$host_name !== $host && ($h->inAlias($alias) || $h->inRedirect($alias))
			) throw new \InvalidArgumentException("Duplicate alias name {$alias}");
		}
	}

	private function in(string $host, \Closure $call): bool
	{
		$host = Host::filter($host);
		return $this->offsetExists($host) ? $call($this->hosts[$host]) : false;
	}

	private function has( string $host, string $compare, \Closure $callback ): bool
	{
		$host = Host::filter($host);
		if( $host )
		{
			if($this->offsetExists($host))
			{
				return false;
			}
			foreach($this->hosts as $host_name => $h)
			{
				if($callback($h, $host))
					return $compare ? $host === $host_name : true;
			}
		}
		return false;
	}

	private function tap($value, \Closure $callback)
	{
		return $callback($value);
	}
}