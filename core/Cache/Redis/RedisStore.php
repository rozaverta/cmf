<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:16
 */

namespace EApp\Cache\Redis;

use EApp\Cache\CacheDriverInterface;
use EApp\Cache\DatabaseHash;
use EApp\Cache\Properties\Property;
use EApp\Cache\Properties\PropertyMemory;
use EApp\Cache\Properties\PropertyStats;
use EApp\Cache\Store;
use Predis\Client;

class RedisStore extends Store
{
	use RedisClientTrait;

	public function __construct(Client $client, string $store_name, int $life = 0 )
	{
		parent::__construct($store_name, $life);
		$this->setRedis($client);
	}

	public function createFactory( string $name, string $prefix = "", array $properties = [], int $life = null ): CacheDriverInterface
	{
		$value = new RedisDriver($this->getRedis(), new DatabaseHash($name, $prefix, $properties));
		$value->load(is_null($life) ? $this->getLife() : $life);
		return $value;
	}

	public function flush( string $prefix = null ): bool
	{
		if( is_null($prefix) || ! strlen($prefix) )
		{
			return $this->commandBool("flushdb");
		}

		$keys = $this->command("keys",addcslashes($prefix, "*") . "*");
		if( is_array($keys) )
		{
			return count($keys) < 1 ? true : $this->commandBool("del", $keys);
		}

		return false;
	}

	public function info(): array
	{
		$info = [];

		$info[] = new Property("driver", "redis");
		$info[] = new Property("driver_version", $this->getRedis()->getProfile()->getVersion());

		foreach($this->getInfo("server") as $name => $value)
		{
			$info[] = new Property($name, $value);
		}

		return $info;
	}

	public function stats(): array
	{
		$stats = [];

		foreach($this->getInfo("stats") as $name => $value)
		{
			$stats[] = new PropertyStats($name, $value);
		}

		foreach($this->getInfo("memory") as $name => $value)
		{
			$stats[] = new Property($name, $value);
		}

		$keys = $this->command("keys", "*");
		if( is_array($keys) && count($keys) > 0 )
		{
			$increment = count($stats);
			$rkey = [];

			foreach($keys as $key)
			{
				$size = $this->command("strlen", $key);

				$pos = strpos($key, "/", 1);
				if( $pos !== false )
				{
					$key = substr($key, 0, $pos);
				}

				if( ! isset($rkey[$key]) )
				{
					$rkey[$key] = $increment;
					$stats[$increment++] = new PropertyMemory($key, $size, 1);
				}
				else
				{
					$stats[$rkey[$key]]->add($size);
				}
			}
		}

		return $stats;
	}

	private function getInfo( string $lower_key ): array
	{
		$info = $this->command("info", $lower_key);
		if( is_array($info) )
		{
			$key = key($info);
			if( strtolower($key) === $lower_key && is_array($info[$key]) )
			{
				return $info[$key];
			}
		}

		return [];
	}
}