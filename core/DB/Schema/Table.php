<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.09.2017
 * Time: 19:14
 */

namespace EApp\DB\Schema;

use EApp\ModuleCore;
use EApp\Support\Json;
use EApp\Support\Collection;
use EApp\System\Files\FileResource;
use InvalidArgumentException;
use EApp\Cache;
use EApp\Component\Module;

/**
 * Class SchemaManager
 * @package EApp\DB
 */
class Table extends TableResource
{
	/**
	 * @var Cache
	 */
	protected $cache;

	protected $module_id = 0;

	public function __construct( $table )
	{
		$this->table = $table;
		$this->cache = new Cache($table, 'table_schema');

		if( $this->cache->ready() )
		{
			$data = $this->cache->getContentData();

			$this->reload($data["items"]);
			$this->module_id = $data["module_id"];
			$this->filters = $data["filters"];
			$this->indexes = new Collection($data["indexes"]);
			$this->extends = $data["extends"];
		}
		else
		{
			$this->reloadDataBase();
		}
	}

	/**
	 * Get module instance
	 *
	 * @return \EApp\Component\Module|null
	 */
	public function getModule()
	{
		if( is_null($this->module) )
		{
			$this->module = $this->module_id > 0 ? Module::cache( $this->module_id ) : new ModuleCore();
		}
		return parent::getModule();
	}

	public function reloadDataBase()
	{
		if( $this->readFromCache() )
		{
			$this->cache->clean();
		}

		$row = \DB::table("scheme_tables")
			->where("name", $this->table)
			->first();

		if( !$row )
		{
			throw new InvalidArgumentException("Table '{$this->table}' not found in the database");
		}

		$this->module_id = (int) $row->module_id;
		$this->load(
			new FileResource(
				"db_" . $this->table,
				null,
				$this->module_id > 0 ? Module::cache( $this->module_id ) : new ModuleCore()
			)
		);

		$this->cache->write([
			"items" => $this->items,
			"indexes" => $this->indexes->getAll(),
			"filters" => $this->filters,
			"extends" => $this->extends,
			"module_id" => $this->module_id
		]);
	}

	public static function cache( $table )
	{
		static $cache = [];
		if( !isset($cache[$table]) )
		{
			$cache[$table] = new self($table);
		}
		return $cache[$table];
	}

	public static function toJsonArray( $value )
	{
		if( is_object($value) )
		{
			return get_object_vars($value);
		}
		if( is_array($value) )
		{
			return $value;
		}
		if( is_string($value) )
		{
			return Json::parse($value, true);
		}
		return [];
	}

	public function readFromCache()
	{
		return $this->cache->ready();
	}
}