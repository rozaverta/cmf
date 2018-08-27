<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:55
 */

namespace EApp\Cache;

abstract class Factory implements CacheFactoryInterface
{
	/**
	 * @var Hash
	 */
	protected $hash;

	protected $life = 0;

	public function __construct( Hash $hash )
	{
		$this->hash = $hash;
	}

	public function load( int $life = 0 )
	{
		$this->life = $life;
	}

	public function export( $data ): bool
	{
		if( ! is_null($data) )
		{
			return $this->exportData($data);
		}

		if( $this->has() )
		{
			$this->forget();
		}

		return false;
	}

	abstract protected function exportData( $data ): bool;

	protected function getKey(): string
	{
		return $this->hash->getHash();
	}
}