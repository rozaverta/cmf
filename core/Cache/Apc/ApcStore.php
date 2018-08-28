<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 21:42
 */

namespace EApp\Cache\Apc;

use EApp\Cache\CacheDriverInterface;
use EApp\Cache\DatabaseHash;
use EApp\Cache\Properties\Property;
use EApp\Cache\Properties\PropertyMemory;
use EApp\Cache\Properties\PropertyStats;
use EApp\Cache\Store;

class ApcStore extends Store
{
	protected $prefix;

	public function __construct(string $store_name, string $prefix = "", int $life = 0)
	{
		parent::__construct($store_name, $life);
		$this->prefix = $prefix;
	}

	public function createFactory( string $name, string $prefix = "", array $properties = [], int $life = null ): CacheDriverInterface
	{
		$value = new ApcDriver(new DatabaseHash($name, $this->prefix . $prefix, $properties));
		$value->load(is_null($life) ? $this->getLife() : $life);
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

	public function info(): array
	{
		$info = [];

		$info[] = new Property("driver", "APCu");
		$info[] = new Property("default_life", $this->getLife());

		$all = apcu_cache_info(true);
		if( is_array($all) )
		{
			foreach($all as $name => $value)
			{
				if( !is_array($value) )
				{
					$info[] = new Property($name, $value);
				}
			}
		}

		$this->cli($info);
		return $info;
	}

	public function stats(): array
	{
		/** @var PropertyMemory[] $memories */
		$items = 0;
		$bytes = 0;
		$memories = [];

		$info = apcu_cache_info();
		if( isset($info["cache_list"]) && is_array($info["cache_list"]) && count($info["cache_list"]) )
		{
			$increment = 0;
			$rkey = [];

			foreach($info["cache_list"] as $item)
			{
				$key  = $item["info"];
				$size = $item["mem_size"];
				$pos  = strpos($key, "/", 1);

				++ $items;
				$bytes += $size;

				if( $pos !== false )
				{
					$key = substr($key, 0, $pos);
				}

				if( ! isset($rkey[$key]) )
				{
					$rkey[$key] = $increment;
					$memories[$increment++] = new PropertyMemory($key, $size, 1);
				}
				else
				{
					$memories[$rkey[$key]]->add($size);
				}
			}
		}

		$stats = [
			new PropertyStats("items", $items),
			new PropertyStats("bytes", $bytes)
		];

		if(count($memories))
		{
			$stats = array_merge($stats, array_values($memories));
		}

		$this->cli($stats);
		return $stats;
	}

	protected function cli( & $info )
	{
		$cli = function_exists("php_sapi_name") && strpos( php_sapi_name(), "cli") !== false;
		if($cli)
		{
			$info[] = new Property("warning", "CLI mode. For show stats or show info or clearing cache you must use web interface");
		}
	}
}