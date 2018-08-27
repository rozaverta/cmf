<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2018
 * Time: 19:45
 */

namespace EApp\Cache\Redis;

use EApp\Cache\Hash;
use EApp\Cache\Factory;
use Predis\Client;

class RedisFactory extends Factory
{
	use RedisClientTrait;

	public function __construct( Client $client, Hash $hash )
	{
		parent::__construct( $hash );
		$this->setRedis($client);
	}

	public function has(): bool
	{
		return $this->commandBool('exists', $this->getKey() );
	}

	public function set( string $value ): bool
	{
		$set = $this->commandBool("set", $this->getKey(), $value);
		if( $set && $this->life > 0 )
		{
			$set = $this->commandBool("expire", $this->getKey(), $this->life);
		}
		return $set;
	}

	public function get()
	{
		$this->has() ? $this->command("get", $this->getKey()) : null;
	}

	public function import()
	{
		return $this->has() ? unserialize($this->command("get", $this->getKey())) : null;
	}

	public function forget(): bool
	{
		return $this->has() ? $this->commandBool("del", $this->getKey()) : true;
	}

	protected function exportData( $data ): bool
	{
		return $this->set(serialize($data));
	}
}