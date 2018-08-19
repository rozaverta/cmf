<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:04
 */

namespace EApp\Cache;

use EApp\Cache\Database\DatabaseStore;
use EApp\Cache\Filesystem\FilesystemStore;
use EApp\Filesystem\Filesystem;
use EApp\App;
use EApp\Prop;

class Manager
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
	 * Get driver name
	 *
	 * @return string
	 */
	public function getDriverName(): string
	{
		return $this->config["driver"];
	}

	/**
	 * Get store
	 *
	 * @return CacheStoreInterface
	 */
	public function getStore(): CacheStoreInterface
	{
		return $this->store;
	}

	/**
	 * Get cache config
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
		throw new \InvalidArgumentException("TODO not support memcache");

		$config = $this->getConfig();

		$memcached = connect(
			$config['servers'],
			$config['persistent_id'] ?? null,
			$config['options'] ?? [],
			array_filter($config['sasl'] ?? [])
		);

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
	 * Create an instance of the database cache driver.
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