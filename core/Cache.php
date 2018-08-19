<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 0:26
 */

namespace EApp;

/**
 * Class Cache
 */
class Cache
{
	/**
	 * @var \EApp\Cache\CacheValueInterface
	 */
	private $factory;

	private static $manager = null;

	public function __construct( string $name, string $prefix = "", array $data = [] )
	{
		$life = null;
		if( isset($data["life"]) && is_int($data["life"]) )
		{
			$life = $data["life"];
			unset($data["life"]);
		}

		$store = self::store();
		$this->factory = $store->getValue($store->getKeyName($name, $prefix, $data), $life);
	}

	/**
	 * @return Cache\Manager
	 */
	public static function manager(): Cache\Manager
	{
		if( ! isset(self::$manager) )
		{
			self::$manager = new Cache\Manager( Prop::cache("cache") );
		}
		return self::$manager;
	}

	/**
	 * @return \EApp\Cache\CacheStoreInterface
	 */
	public static function store(): Cache\CacheStoreInterface
	{
		return self::manager()->getStore();
	}

	public function ready()
	{
		return $this->factory->has();
	}

	public function set( string $value )
	{
		return $this->factory->set( $value );
	}

	public function get()
	{
		return $this->factory->get();
	}

	public function import()
	{
		return $this->factory->import();
	}

	public function export($data): bool
	{
		return $this->factory->export($data);
	}

	public function forget(): bool
	{
		return $this->factory->forget();
	}
}