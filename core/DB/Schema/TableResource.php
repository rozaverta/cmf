<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.04.2018
 * Time: 21:09
 */

namespace EApp\DB\Schema;

use EApp\Support\Collection;
use EApp\System\Files\FileResource;

class TableResource extends Collection
{
	/**
	 * @var string
	 */
	protected $table = "";

	/**
	 * @var \EApp\Component\Module|null
	 */
	protected $module = null;

	protected $indexes;

	protected $filters = [];

	protected $extends = [];

	public function __construct( FileResource $file )
	{
		parent::__construct();
		$this->load($file);
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return $this->table;
	}

	/**
	 * Get module instance
	 *
	 * @return \EApp\Component\Module|null
	 */
	public function getModule()
	{
		return $this->module;
	}

	public function fieldFilter($name)
	{
		return isset( $this->filters[$name] ) ? $this->filters[$name] : null;
	}

	public function extend($name, $default = null)
	{
		return isset( $this->extends[$name] ) ? $this->extends[$name] : $default;
	}

	public function indexes()
	{
		return $this->indexes;
	}

	protected function load( FileResource $file)
	{
		if( $file->getType() !== "#/data_base_table" )
		{
			throw new \InvalidArgumentException("Invalid resource type");
		}

		$module = $file->getModule();
		if( $module === null )
		{
			throw new \InvalidArgumentException("ModuleComponent is not used module for database table");
		}

		$this->table  = $file->get("name");
		$this->module = $module;

		$fields = $file->getOr("fields", []);
		$indexes = $file->getOr("indexes", []);
		$primaryKeys = $file->getOr("primaryKey", []);
		$filters = $file->getOr("filters", []);
		if($primaryKeys)
		{
			if( !is_array($primaryKeys) )
			{
				$primaryKeys = [$primaryKeys];
			}
		}

		count($fields) && $this->loadFields($fields);
		count($primaryKeys) && $this->loadPrimaryKeys($primaryKeys);
		$this->loadIndexes($indexes);
		count($filters) && $this->loadFilters($filters);
		$this->extends = $file->getOr("extends", []);
	}

	protected function loadIndexes( array $indexes )
	{
		if(count($indexes))
		{
			$indexes = $this->makeIndexes($indexes);
		}

		$this->indexes = new Collection($indexes);
	}

	protected function loadPrimaryKeys( array $fields )
	{
		foreach( $fields as $field )
		{
			if( isset($this->items[$field]) )
			{
				$this->items[$field]["primary"] = true;
			}
		}
	}

	protected function loadFilters( array $filters )
	{
		foreach($filters as $filter)
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

	protected function loadFields( array $fields )
	{
		$items = [];

		foreach( $fields as $key => $prop )
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

			if( isset($items[$name]) )
			{
				throw new \InvalidArgumentException("Duplicate column name '{$name}'");
			}

			// create flags from type string [ integer:zerofill ]

			$flag = [];
			$type = isset($prop["type"]) ? strtolower($prop["type"]) : "string";
			if( strpos($type, ":") !== false )
			{
				$flag = explode(":", $type);
				$type = array_shift($flag);
			}

			if( ($pos = strpos($type, '/')) !== false )
			{
				$subtype = substr($type, $pos+1);
				$type = substr($type, 0, $pos);
			}
			else
			{
				$subtype = $type;
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
					"notnull" => ! isset($prop["notnull"]) || $prop["notnull"] === true,
					"index" => in_array("index", $flag),
					"unique" => in_array("unique", $flag),
					"primary" => in_array("primary", $flag),
					"autoincrement" => in_array("autoincrement", $flag),
					"unsigned" => $number && in_array("unsigned", $flag),
					"zerofill" => $number && in_array("zerofill", $flag),
					"fixed" => in_array("fixed", $flag),
					"length" => isset($prop["length"]) && is_int($prop["length"]) ? $prop["length"] : null,
					"precision" => isset($prop["precision"]) && is_int($prop["precision"]) ? $prop["precision"] : null,
					"scale" => isset($prop["scale"]) && is_int($prop["scale"]) ? $prop["scale"] : null,
				];

			foreach(["default", "title", "comment"] as $key)
			{
				if( isset($prop[$key]) )
				{
					$field[$key] = $prop[$key];
				}
			}

			$items[$name] = $field;

			if( isset($prop["filter"]) )
			{
				$this->addFilter($name, $prop["filter"]);
			}
		}

		$this->reload($items);
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