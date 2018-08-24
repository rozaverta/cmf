<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 18:09
 */

namespace EApp\Traits;

use EApp\Cache;

trait CacheNameInstanceTrait
{
	use CacheInstanceTrait;

	private static $cache = [];

	public static function cache( string $name )
	{
		if( ! self::cacheIs($name) )
		{
			$cache = self::createCache($name);
			if($cache->ready())
			{
				$instance = self::newCacheInstance($cache->import());
			}
			else
			{
				$instance = new static($name);
				$cache->export($instance->exportCacheData());
			}

			self::setCache($name, $instance);
		}

		return self::$cache[$name];
	}

	protected static function cacheIs( string $name ): bool
	{
		return isset(self::$cache[$name]);
	}

	protected static function setCache( string $name, $instance )
	{
		/** @var CacheInstanceTrait $instance */
		self::$cache[$name] = $instance->setLoadedFromCache();
	}

	abstract function __construct(string $name);

	abstract protected static function createCache( string $name ): Cache;
}