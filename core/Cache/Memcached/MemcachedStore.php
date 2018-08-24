<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:16
 */

namespace EApp\Cache\Memcached;

use EApp\Cache\CacheStoreInterface;
use EApp\Cache\CacheValueInterface;
use EApp\Cache\DatabaseKeyName;
use EApp\Cache\KeyName;
use Memcached;

class MemcachedStore implements CacheStoreInterface
{
	use ConnectionTrait;

	protected $prefix;

	protected $life = 0;

	public function __construct( Memcached $connection, string $prefix = "", int $life = 0 )
	{
		$this->setConnection($connection);
		$this->prefix = $prefix;
		$this->life = $life;
	}

	public function getValue( KeyName $key_name, int $life = null ): CacheValueInterface
	{
		$value = new MemcachedValue($this->getConnection(), $key_name);
		$value->load(is_null($life) ? $this->life : $life);
		return $value;
	}

	public function flush( string $prefix = null ): bool
	{
		if( ! is_null($prefix) )
		{
			throw new \InvalidArgumentException("Prefix flush is not support for Memcached driver");
		}

		return $this->result(
			$this->getConnection()->flush()
		);
	}

	public function getKeyName( string $key_name, string $prefix = "", array $properties = [] ): KeyName
	{
		$prefix = $this->prefix . $prefix;
		return new DatabaseKeyName($key_name, $prefix, $properties);
	}
}