<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.08.2018
 * Time: 22:04
 */

namespace EApp\Cache\Database;

use EApp\App;
use EApp\Database\Connection;
use EApp\Database\QueryException;

trait DatabaseConnectionTrait
{
	/**
	 * @var Connection
	 */
	protected $connection;

	protected $table;

	protected function setConnection( Connection $connection, string $table = "cache" )
	{
		$this->connection = $connection;
		$this->table = $table;

		// table scheme

		// - id
		// - key_name
		// - key_prefix (group)
		// - value
		// - size
		// - updated_at
	}

	/**
	 * @return Connection
	 */
	public function getConnection(): Connection
	{
		return $this->connection;
	}

	/**
	 * @return string
	 */
	public function getTable(): string
	{
		return $this->table;
	}

	protected function table()
	{
		return $this
			->getConnection()
			->table($this->getTable());
	}

	protected function fetch(\Closure $callback, $argument = null)
	{
		try
		{
			$result = $callback(is_null($argument) ? $this->getConnection() : $argument);
		}
		catch( QueryException $e )
		{
			App::Log($e);
			return false;
		}

		return $result;
	}
}