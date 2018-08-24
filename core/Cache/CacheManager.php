<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:04
 */

namespace EApp\Cache;

use EApp\Cache\Apc\ApcStore;
use EApp\Cache\Database\DatabaseStore;
use EApp\Cache\Filesystem\FilesystemStore;
use EApp\Cache\Memcached\MemcachedStore;
use EApp\Filesystem\Filesystem;
use EApp\App;
use EApp\Prop;

class CacheManager
{
	protected $config;

	/**
	 * @var CacheStoreInterface
	 */
	protected $store;

	public function __construct( Prop $config )
	{
		if( !$config->getIs("driver") )
		{
			$config->set("driver", "file");
		}

		$this->config = $config;
		$name = $config["driver"];
		$method = "create" . ucfirst($name) . "Driver";

		if( method_exists($this, $method) )
		{
			$this->store = $this->{$method}();
		}
		else
		{
			throw new \InvalidArgumentException("Invalid cache driver '{$name}'");
		}
	}

	/**
	 * GetTrait driver name
	 *
	 * @return string
	 */
	public function getDriverName(): string
	{
		return $this->config["driver"];
	}

	/**
	 * GetTrait store
	 *
	 * @return CacheStoreInterface
	 */
	public function getStore(): CacheStoreInterface
	{
		return $this->store;
	}

	/**
	 * GetTrait cache config
	 *
	 * @return Prop
	 */
	public function getConfig(): Prop
	{
		return $this->config;
	}

	/**
	 * Create an instance of the Memcached cache driver
	 *
	 * @return \EApp\Cache\Memcached\MemcachedStore
	 */
	protected function createMemcachedDriver()
	{
		$config = $this->getConfig();
		$memcached = new \Memcached($config->getOr("persistent_id", null));

		if($config->isArray("options"))
		{
			$memcached->setOptions($config->get("options"));
		}

		if($config->getIs("username") && method_exists($memcached, "setSaslAuthData"))
		{
			$memcached->setSaslAuthData(
				$config->get("username"),
				$config->getOr("password", "")
			);
		}

		if($config->isArray("servers"))
		{
			$memcached->addServers($config->get("servers"));
		}
		else
		{
			$memcached->addServer(
				$config->getOr("host", "127.0.0.1"),
				$config->getOr("port", 11211),
				$config->getOr("weight", 0)
			);
		}

		return new MemcachedStore($memcached);
	}

	/**
	 * Create an instance of the Redis cache driver
	 *
	 * @return \EApp\Cache\Redis\RedisStore
	 */
	protected function createRedisDriver()
	{
		throw new \InvalidArgumentException("TODO not support redis");
	}

	/**
	 * Create an instance of the database cache driver
	 *
	 * @return \EApp\Cache\Database\DatabaseStore
	 */
	protected function createDatabaseDriver()
	{
		$config = $this->getConfig();
		return new DatabaseStore(
			App::Database()->getConnection($config->getOr("connection", null)),
			$config->getOr("table", "cache"),
			$config->getOr("life", 0)
		);
	}

	/**
	 * Create an instance of the APC cache driver
	 *
	 * @return \EApp\Cache\Apc\ApcStore
	 */
	protected function createApcDriver()
	{
		$config = $this->getConfig();
		return new ApcStore(
			$config->getOr("prefix", ""),
			$config->getOr("life", 0)
		);
	}

	/**
	 * Create an instance of the file cache driver
	 *
	 * @return \EApp\Cache\Filesystem\FilesystemStore
	 */
	protected function createFileDriver()
	{
		$config = $this->getConfig();
		return new FilesystemStore(
			Filesystem::getInstance(),
			$config->getOr("directory", "cache"),
			$config->getOr("life", 0)
		);
	}
}