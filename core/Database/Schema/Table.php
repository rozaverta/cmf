<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.09.2017
 * Time: 19:14
 */

namespace EApp\Database\Schema;

use EApp\ModuleCore;
use EApp\Support\Collection;
use EApp\Support\Exceptions\NotFoundException;
use EApp\System\Fs\FileResource;
use EApp\Cache;
use EApp\Component\Module;

/**
 * Class SchemaManager
 * @package EApp\DB
 */
class Table extends TableData
{
	/**
	 * @var Cache
	 */
	protected $cache;

	public function __construct( string $table )
	{
		$this->cache = new Cache($table, 'table_schema');

		if( $this->cache->ready() )
		{
			$data = $this->cache->import();

			parent::__construct(
				$data["table_name"],
				$data["module"],
				new Collection($data["items"]),
				$data["indexes"],
				$data["filters"],
				$data["extra"]
			);
		}
		else
		{
			$loader = $this->getLoader($table);

			parent::__construct(
				$loader->getTableName(),
				$loader->getModule(),
				$loader->getColumns(),
				$loader->getIndexes(),
				$loader->getFilters(),
				$loader->getExtras()
			);

			$this->writeCache();
		}
	}

	public function reloadDataBase()
	{
		$this->cache->forget();

		$loader = $this->getLoader($this->getTableName());
		$module = $loader->getModule();

		if( $module->getId() !== $this->getModule()->getId() )
		{
			$this->setModule($module);
		}

		$this->items    = $loader->getColumns();
		$this->indexes  = $loader->getIndexes();
		$this->filters  = $loader->getFilters();
		$this->extra    = $loader->getExtras();

		$this->writeCache();

		return $this;
	}

	/**
	 * @param $table
	 * @return Table
	 */
	public static function cache( $table ): self
	{
		static $cache = [];
		if( !isset($cache[$table]) )
		{
			$cache[$table] = new self($table);
		}
		return $cache[$table];
	}

	/**
	 * @return bool
	 */
	public function readFromCache(): bool
	{
		return $this->cache->ready();
	}

	private function writeCache()
	{
		$this->cache->export([
			"table_name" => $this->getTableName(),
			"module" => $this->getModule(),
			"items" => $this->items,
			"indexes" => $this->indexes,
			"filters" => $this->filters,
			"extra" => $this->extra
		]);
	}

	/**
	 * @param string $table
	 * @return ResourceLoader
	 * @throws NotFoundException
	 */
	private function getLoader( string $table )
	{
		$row = \DB::table("scheme_tables")
			->where("name", $table)
			->first();

		if( ! $row )
		{
			throw new NotFoundException("Table '{$table}' not found in the database");
		}

		return new ResourceLoader(
			new FileResource(
				"db_" . $table,
				null,
				$row->module_id > 0 ? Module::cache( (int) $row->module_id ) : new ModuleCore()
			)
		);
	}
}