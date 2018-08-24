<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:17
 */

namespace EApp\Cache\Database;

use EApp\Cache\CacheStoreInterface;
use EApp\Cache\CacheValueInterface;
use EApp\Cache\DatabaseKeyName;
use EApp\Cache\KeyName;
use EApp\Database\Connection;
use EApp\Database\Query\Builder;

class DatabaseStore implements CacheStoreInterface
{
	use DatabaseConnectionTrait;

	protected $life = 0;

	public function __construct( Connection $connection, string $table = "cache", int $life = 0 )
	{
		$this->setConnection($connection, $table);
		$this->life = $life;
	}

	public function flush( string $prefix = null ): bool
	{
		$table = $this->table();

		if( is_null($prefix) )
		{
			return $this->fetch(function(Builder $table) {
				$table->truncate();
				return true;
			}, $table);
		}

		$prefix = (new DatabaseKeyName("", $prefix))->keyPrefix();
		$table
			->where("key_prefix", '=', $prefix)
			->orWhere("key_prefix", "like", addcslashes($prefix, "%_") . "%");

		return $this->fetch(function(Builder $table) {
			return $table->delete() !== false;
		}, $table);
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