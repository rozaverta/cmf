<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 19:26
 */

namespace EApp\Cache\Memcached;

use EApp\Cache\DatabaseHash;
use EApp\Cache\Hash;
use EApp\Cache\Factory;
use Memcached;

class MemcachedFactory extends Factory
{
	use MemcachedConnectionTrait;

	private $ready = false;

	private $life = 0;

	/**
	 * @var null | object
	 */
	private $row = null;

	public function __construct( Memcached $connection, Hash $key_name )
	{
		if( ! $key_name instanceof DatabaseHash )
		{
			throw new \InvalidArgumentException("You must used the " . DatabaseHash::class . ' object instance for the ' . __CLASS__ . ' constructor');
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
			$row = $this->getConnection()->get( $this->key_name->getHash() );
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
					$this->key_name->getHash()
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
				$this->key_name->getHash(),
				$data,
				$this->life > 0 ? time() + $this->life : 0
			)
		);
	}
}