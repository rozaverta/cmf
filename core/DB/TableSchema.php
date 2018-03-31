<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.09.2017
 * Time: 19:14
 */

namespace EApp\DB;

use EApp\Support\Json;
use EApp\Support\Collection;
use InvalidArgumentException;
use EApp\Cache;
use EApp\Component\Module;

/**
 * Class SchemaManager
 * @package EApp\DB
 */
class TableSchema extends Collection
{
	const VER = "1.0.0";

	protected $table = '';

	/**
	 * @var Cache
	 */
	protected $cache;

	protected $nullable;

	protected $filters = [];

	/**
	 * @var Collection
	 */
	protected $indexes;

	protected $extend = [];

	public function __construct( $table )
	{
		$this->table = $table;
		$this->cache = new Cache($table, 'table_schema');

		if( $this->cache->ready() )
		{
			$data = $this->cache->getContentData();
			if( $data["ver"] === self::VER )
			{
				parent::__construct($data["items"]);
				$this->filters = $data["filters"];
				$this->indexes = new Collection($data["indexes"]);
				$this->extend  = $data["extend"];
			}
		}

		if( !count($this->items) )
		{
			$this->reload();
		}
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

	public function getTableName()
	{
		return $this->table;
	}

	public function nullable($name)
	{
		if( is_null($this->nullable) )
		{
			$this->nullable = [];
			foreach($this->items as $prop)
			{
				if( $prop["nullable"] )
				{
					$this->nullable[] = $prop["name"];
				}
			}
		}

		return in_array($name, $this->nullable);
	}

	public function fieldFilter($name)
	{
		return isset( $this->filters[$name] ) ? $this->filters[$name] : null;
	}

	public function extend($name, $default = null)
	{
		return isset( $this->extend[$name] ) ? $this->extend[$name] : $default;
	}

	public function indexes()
	{
		return $this->indexes;
	}

	public function reload($items = [])
	{
		static $alias =
			[
				"int" => "integer",
				"bool" => "boolean"
			];

		if( $this->cache->ready() )
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

		// file name
		// custom scheme

		$file = APP_DIR . "resource" . DIRECTORY_SEPARATOR . "db_" . $row->name . ".json";

		if( ! file_exists($file) )
		{
			// load default scheme

			$resource = 'db_' . $row->name;
			if( !empty($row->resource) )
			{
				$resource = preg_replace('/\.json$/i', '', $row->resource);
			}

			// create file path

			$file = ($row->module_id > 0 ? ( new Module($row->module_id))->get("path") : CORE_DIR ) . 'resources' . DIRECTORY_SEPARATOR . $resource . '.json';
			if( !file_exists($file) )
			{
				throw new \Exception("Resource file for '{$row->name}' table not found.");
			}
		}

		// parse json file

		$data = Json::parse(file_get_contents($file), true);
		if( isset($data[$row->name]) && is_array($data[$row->name]) )
		{
			$data = $data[$row->name];
		}

		if( !isset($data["fields"]) )
		{
			throw new InvalidArgumentException("Invalid format for '{$this->table}' table scheme");
		}

		// required fields

		$this->items = [];
		$this->nullable = [];

		foreach( $data["fields"] as $key => $prop )
		{
			if( is_string($prop) )
			{
				$prop = ["type" => $prop];
			}

			// check field name

			$name = isset($prop["name"]) ? $prop["name"] : $key;
			if( empty($name) || is_numeric($name) )
			{
				throw new \InvalidArgumentException("Required parameter 'name' not specified");
			}

			// check duplicate field name

			if( isset($this->items[$name]) )
			{
				throw new \InvalidArgumentException("Duplicate column name '{$name}'");
			}

			// create flags from type string [ int:zerofill:nullable ]

			$flag = [];
			$type = isset($prop["type"]) ? strtolower($prop["type"]) : "string";
			if( strpos($type, ":") !== false )
			{
				$flag = explode(":", $type);
				$type = array_shift($flag);
			}

			if( $pos = strpos($type, '/') !== false )
			{
				$subtype = substr($type, $pos+1);
				$type = substr($type, 0, $pos);
			}
			else
			{
				$subtype = $type;
			}

			// check alias type

			if( isset($alias[$type]) )
			{
				$type = $alias[$type];
			}

			// field is numeric, for unsigned & zerofill

			$number = $type == "integer" || $type == "number";

			// create full field data

			$field =
				[
					"name" => $name,
					"type" => $type,
					"subtype" => $subtype,
					"title" => "",
					"description" => "",
					"nullable" => isset($prop["nullable"]) && $prop["nullable"] === true,
					"index" => in_array("index", $flag),
					"unique" => in_array("unique", $flag),
					"primary" => in_array("primary", $flag),
					"autoincrement" => in_array("autoincrement", $flag),
					"unsigned" => $number && in_array("unsigned", $flag),
					"zerofill" => $number && in_array("zerofill", $flag),
				];

			if( $field["nullable"] )
			{
				$this->nullable[] = $name;
			}

			foreach(["default", "title", "description", "length"] as $key)
			{
				if( isset($prop[$key]) )
				{
					$field[$key] = $prop[$key];
				}
			}

			$this->items[$name] = $field;

			if( isset($prop["filter"]) )
			{
				$this->addFilter($name, $prop["filter"]);
			}
		}

		// primary keys

		if( isset($data['primaryKey']) )
		{
			foreach( (array) $data['primaryKey'] as $field )
			{
				if( isset($this->items[$field]) )
				{
					$this->items[$field]["primary"] = true;
				}
			}
		}

		$indexes = isset($data["indexes"]) && is_array($data["indexes"]) ? $this->makeIndexes($data["indexes"]) : [];
		$this->indexes = new Collection($indexes);

		if( isset($data["filters"]) && is_array($data["filters"]) )
		{
			foreach($data["filters"] as $filter)
			{
				if( isset($filter['name'], $filter['filter']) )
				{
					$name = $filter['name'];
					if( is_string($filter['filter']) && isset($filter['properties']) && is_array($filter['properties']) )
					{
						$flt = $filter['properties'];
						$flt['name'] = $filter['filter'];
					}
					else
					{
						$flt = $filter['filter'];
					}

					if( is_array($name) )
					{
						foreach($name as $field_name)
						{
							$this->addFilter($field_name, $flt);
						}
					}
					else
					{
						$this->addFilter($name, $flt);
					}
				}
			}
		}

		$this->cache->write([
			"ver" => self::VER,
			"items" => $this->items,
			"indexes" => $indexes,
			"filters" => $this->filters,
			"extend" => isset($data["extend"]) && is_array($data["extend"]) ? $data["extend"] : []
		]);
	}

	protected function addFilter($name, $filter)
	{
		if( !isset($this->items[$name]) )
		{
			return;
		}

		if( is_array($filter) )
		{
			$keys = array_keys($filter);
			$count = count($keys);
			if( !$count )
			{
				return;
			}

			if( !isset($this->filters[$name]) )
			{
				$this->filters[$name] = [];
			}

			for( $i = 0; $i < $count; $i++ )
			{
				if( $keys[$i] !== $i )
				{
					$this->filters[$name][] = $filter;
					return;
				}
			}

			for( $i = 0; $i < $count; $i++ )
			{
				$this->filters[$name][] = $filter[$i];
			}
		}
		else
		{
			if( !isset($this->filters[$name]) )
			{
				$this->filters[$name] = [];
			}

			$this->filters[$name][] = $filter;
		}
	}

	protected function makeIndexes( $indexes )
	{
		static $types = ['INDEX', 'PRIMARY', 'UNIQUE', 'FULLTEXT'];
		$get = [];

		foreach($indexes as $index)
		{
			if( is_string($index) )
			{
				$index = [
					"name" => $index,
					"fields" => [$index]
				];
			}
			if( ! isset($index['fields']) )
			{
				continue;
			}

			$type = isset($index['type']) ? strtoupper($index['type']) : $types[0];
			if( !in_array($type, $types, true) )
			{
				$type = $types[0];
			}

			$fields = is_array($index['fields']) ? $index['fields'] : [$index['fields']];
			foreach($fields as $field)
			{
				if( !isset($this->items[$field]))
				{
					continue;
				}
			}

			$lower = $type !== 'FULLTEXT' ? strtolower($type) : 'index';
			foreach($fields as $field)
			{
				$this->items[$field][$lower] = true;
				if( $lower !== 'index' )
				{
					$this->items[$field]['index'] = true;
				}
			}

			$get[] = [
				"name" => isset($index["name"]) ? (string) $index["name"] : $fields[0],
				"type" => $type,
				"fields" => $fields
			];
		}

		return $get;
	}
}