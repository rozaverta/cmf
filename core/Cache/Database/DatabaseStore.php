<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:17
 */

namespace EApp\Cache\Database;


use EApp\App;
use EApp\Cache\CacheStoreInterface;
use EApp\Cache\CacheValueInterface;
use EApp\Cache\KeyName;
use EApp\Database\Connection;
use EApp\Database\QueryException;

class DatabaseStore implements CacheStoreInterface
{
	/**
	 * @var Connection
	 */
	protected $connection;

	protected $table;

	protected $life = 0;

	public function __construct( Connection $connection, string $table = "cache", int $life = 0 )
	{
		$this->connection = $connection;
		$this->table = $table;
		$this->life = $life;

		// table scheme

		// - id
		// - key_name
		// - key_prefix (group)
		// - value
		// - size
		// - updated_at
	}

	public function flush( string $prefix = null ): bool
	{
		$table = $this->connection->table($this->table);
		if( ! is_null($prefix) )
		{
			$key_name = new DatabaseKeyName("", $prefix);
			$table->where("key_prefix", '=', $key_name->getKeyPrefix());
		}

		try {
			$table->delete();
		}
		catch( QueryException $e ) {
			App::Log($e);
			return false;
		}

		return true;
	}

	public function getValue( KeyName $key_name, int $life = null ): CacheValueInterface
	{
		$value = new DatabaseValue($this->connection, $this->table, $key_name);
		$value->load(is_null($life) ? $this->life : $life);
		return $value;
	}

	public function getKeyName( string $name, string $prefix = "", array $properties = [] ): KeyName
	{
		return new DatabaseKeyName($name, $prefix, $properties);
	}
}