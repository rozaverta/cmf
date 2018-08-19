<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:55
 */

namespace EApp\Cache;

abstract class Value implements CacheValueInterface
{
	/**
	 * @var KeyName
	 */
	protected $key_name;

	public function __construct( KeyName $key_name )
	{
		$this->key_name = $key_name;
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
}