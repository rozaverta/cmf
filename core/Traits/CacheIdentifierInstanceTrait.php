<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 18:09
 */

namespace EApp\Traits;

use EApp\Cache;

trait CacheIdentifierInstanceTrait
{
	use CacheInstanceTrait;

	private static $cache = [];

	public static function cache( int $id )
	{
		if( ! self::cacheIs($id) )
		{
			$cache = self::createCache($id);
			if($cache->ready())
			{
				$instance = self::newCacheInstance($cache->import());
			}
			else
			{
				$instance = new static($id);
				$cache->export($instance->exportCacheData());
			}

			self::setCache($id, $instance);
		}

		return self::$cache[$id];
	}

	protected static function cacheIs( int $id ): bool
	{
		return isset(self::$cache[$id]);
	}

	protected static function setCache( int $id, $instance )
	{
		/** @var CacheInstanceTrait $instance */
		self::$cache[$id] = $instance->setLoadedFromCache();
	}

	abstract function __construct(int $id);

	abstract protected static function createCache( int $id ): Cache;
}