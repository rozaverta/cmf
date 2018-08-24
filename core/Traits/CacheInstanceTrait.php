<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 1:17
 */

namespace EApp\Traits;

trait CacheInstanceTrait
{
	private $loaded_from_cache = false;

	protected static function newCacheInstance($data)
	{
		if($data instanceof static)
		{
			return $data;
		}

		$ref = new \ReflectionClass(static::class);

		/** @var static $instance */
		$instance = $ref->newInstanceWithoutConstructor();
		$instance->importCacheData($data);

		return $instance;
	}

	abstract protected function importCacheData( $data );

	abstract protected function exportCacheData();

	/**
	 * @param bool $loaded
	 * @return $this
	 */
	protected function setLoadedFromCache( bool $loaded = true )
	{
		$this->loaded_from_cache = $loaded;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isLoadedFromCache(): bool
	{
		return $this->loaded_from_cache;
	}
}