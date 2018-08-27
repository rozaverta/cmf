<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2018
 * Time: 19:24
 */

namespace EApp\Cache\Redis;

use EApp\App;
use Predis\Client;
use Predis\PredisException;
use Predis\Response\Status;

trait RedisClientTrait
{
	/**
	 * @var Client
	 */
	protected $redis;

	protected function setRedis( Client $redis )
	{
		$this->redis = $redis;
	}

	public function getRedis()
	{
		return $this->redis;
	}

	/**
	 * @param $name
	 * @param array ...$arguments
	 * @return mixed
	 */
	protected function command( $name, ... $arguments )
	{
		try {
			$result = $this
				->getRedis()
				->{$name}( ... $arguments );

			if( $result instanceof Status )
			{
				return $result->getPayload() === "OK";
			}
		}
		catch(PredisException $e)
		{
			App::Log($e);
			return false;
		}

		return $result;
	}

	/**
	 * @param $name
	 * @param array ...$arguments
	 * @return bool
	 */
	protected function commandBool( $name, ... $arguments ): bool
	{
		$result = $this->command($name, ... $arguments);

		if( is_bool($result) )
		{
			return $result;
		}

		if( is_int($result) )
		{
			if( in_array($name, ["flushdb", "flushall", "del", "hdel"]) )
			{
				return true;
			}
			else
			{
				return $result > 0;
			}
		}

		return (bool) $result;
	}
}