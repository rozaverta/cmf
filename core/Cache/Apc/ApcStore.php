<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 21:42
 */

namespace EApp\Cache\Apc;

use EApp\Cache\CacheStoreInterface;
use EApp\Cache\CacheValueInterface;
use EApp\Cache\DatabaseKeyName;
use EApp\Cache\KeyName;

class ApcStore implements CacheStoreInterface
{
	protected $prefix;

	protected $life = 0;

	public function __construct(string $prefix = "", int $life = 0)
	{
		$this->prefix = $prefix;
		$this->life = $life;
	}

	public function getValue( KeyName $key_name, int $life = null ): CacheValueInterface
	{
		$value = new ApcValue($key_name);
		$value->load(is_null($life) ? $this->life : $life);
		return $value;
	}

	public function flush( string $prefix = null ): bool
	{
		if( ! is_null($prefix) )
		{
			throw new \InvalidArgumentException("Prefix flush is not support for APCu driver");
		}

		return apcu_clear_cache();
	}

	public function getKeyName( string $key_name, string $prefix = "", array $properties = [] ): KeyName
	{
		$prefix = $this->prefix . $prefix;
		return new DatabaseKeyName($key_name, $prefix, $properties);
	}
}