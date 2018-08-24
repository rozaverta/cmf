<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 22:39
 */

namespace EApp\Cache\Apc;

use EApp\Cache\Value;

class ApcValue extends Value
{
	protected $life = 0;

	public function load( int $life = 0 )
	{
		$this->life = $life;
	}

	public function has(): bool
	{
		return apcu_exists( $this->getKey() );
	}

	public function set( string $value ): bool
	{
		return $this->exportData($value);
	}

	public function get()
	{
		return $this->has() ? (string) apcu_fetch($this->getKey()) : null;
	}

	public function import()
	{
		return $this->has() ? apcu_fetch($this->getKey()) : null;
	}

	public function forget(): bool
	{
		return apcu_delete( $this->getKey() );
	}

	protected function exportData( $data ): bool
	{
		return apcu_store(
			$this->getKey(), $data, $this->life
		);
	}

	protected function getKey(): string
	{
		return $this->key_name->getKey();
	}
}