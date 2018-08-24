<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 19:26
 */

namespace EApp\Cache\Memcached;

use EApp\Cache\DatabaseKeyName;
use EApp\Cache\KeyName;
use EApp\Cache\Value;
use Memcached;

class MemcachedValue extends Value
{
	use ConnectionTrait;

	private $ready = false;

	private $life = 0;

	/**
	 * @var null | object
	 */
	private $row = null;

	public function __construct( Memcached $connection, KeyName $key_name )
	{
		if( ! $key_name instanceof DatabaseKeyName )
		{
			throw new \InvalidArgumentException("You must used the " . DatabaseKeyName::class . ' object instance for the ' . __CLASS__ . ' constructor');
		}
		parent::__construct( $key_name );

		$this->setConnection($connection);
	}

	public function load( int $life = 0 )
	{
		$this->life = $life;
	}

	public function has(): bool
	{
		if( ! $this->ready )
		{
			$this->ready = true;
			$row = $this->getConnection()->get( $this->key_name->getKey() );
			if( $row !== false )
			{
				$this->row = $row;
			}
			else
			{
				$this->result(false);
			}
		}

		return ! is_null($this->row);
	}

	public function set( string $value ): bool
	{
		return $this->exportData( $value );
	}

	public function get()
	{
		return $this->has() ? (string) $this->row : null;
	}

	public function import()
	{
		return $this->has() ? $this->row : null;
	}

	public function forget(): bool
	{
		if( $this->has() )
		{
			$this->ready = false;
			return $this->result(
				$this->getConnection()->delete(
					$this->key_name->getKey()
				)
			);
		}
		else
		{
			return true;
		}
	}

	protected function exportData( $data ): bool
	{
		return $this->result(
			$this->getConnection()->set(
				$this->key_name->getKey(),
				$data,
				$this->life > 0 ? time() + $this->life : 0
			)
		);
	}
}