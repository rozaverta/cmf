<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:17
 */

namespace EApp\Cache\Database;

use EApp\Cache\CacheFactoryInterface;
use EApp\Cache\DatabaseHash;
use EApp\Cache\Properties\Property;
use EApp\Cache\Properties\PropertyLog;
use EApp\Cache\Properties\PropertyMemory;
use EApp\Cache\Properties\PropertyStats;
use EApp\Cache\Store;
use EApp\Database\Connection;
use EApp\Database\Query\Builder;
use EApp\Database\Query\ExpressionWrap;

class DatabaseStore extends Store
{
	use DatabaseConnectionTrait;

	public function __construct( Connection $connection, string $name, string $table = "cache", int $life = 0 )
	{
		parent::__construct($name, $life);
		$this->setConnection($connection, $table);
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

		$prefix = (new DatabaseHash("", $prefix))->keyPrefix();
		$table
			->where("prefix", '=', $prefix)
			->orWhere("prefix", "like", addcslashes($prefix, "%_") . "%");

		return $this->fetch(function(Builder $table) {
			return $table->delete() !== false;
		}, $table);
	}

	public function info(): array
	{
		$info = [];
		$info[] = new Property("driver", "database");
		$info[] = new Property("default_life", $this->life);

		$con = $this->getConnection();
		$info[] = new Property("database_driver", $con->getDriverName());
		$info[] = new Property("database_name", $con->getDatabaseName());
		$info[] = new Property("cache_table", $this->getTable());

		return $info;
	}

	public function stats(): array
	{
		/** @var PropertyMemory[] $memories */
		$stats = [];
		$memories = [];

		$all = $this->fetch(function(Builder $table) {

			return $table
				->groupBy("prefix")
				->get([
					"name",
					"prefix",
					new ExpressionWrap('COUNT(%s) as %s', ['id', 'items']),
					new ExpressionWrap('SUM(%s) as %s', ['size', 'sizes']),
				]);

		}, $this->table());

		if( $all === false )
		{
			$stats[] = new PropertyLog("Error", "Database query failure");
		}
		else
		{
			$count = 0;
			$bytes = 0;
			$increment = 0;
			$keys = [];

			foreach($all as $item)
			{
				$key = empty($item->prefix) ? $item->name : $item->prefix;
				$items = (int) $item->items;
				$sizes = (int) $item->sizes;

				$pos = strpos($key, "/", 1);
				if( $pos !== false )
				{
					$key = substr($key, 0, $pos);
				}

				$count += $items;
				$bytes += $sizes;

				if( ! isset($keys[$key]) )
				{
					$keys[$key] = $increment;
					$memories[$increment++] = new PropertyMemory($key, $sizes, $items);
				}
				else
				{
					$memories[$keys[$key]]->add($sizes);
				}
			}

			$stats = [
				new PropertyStats("items", $count),
				new PropertyStats("bytes", $bytes)
			];

			if(count($memories))
			{
				$stats = array_merge($stats, array_values($memories));
			}
		}

		return $stats;
	}

	public function createFactory( string $name, string $prefix = "", array $properties = [], int $life = null ): CacheFactoryInterface
	{
		$value = new DatabaseFactory($this->connection, $this->table, new DatabaseHash($name, $prefix, $properties));
		$value->load(is_null($life) ? $this->life : $life);
		return $value;
	}
}