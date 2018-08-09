<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.04.2018
 * Time: 21:09
 */

namespace EApp\Database\Schema;

use EApp\Prop;
use EApp\Support\Collection;
use EApp\Support\Traits\GetModuleComponent;
use EApp\System\Fs\FileResource;
use EApp\System\Interfaces\ModuleComponent;

class ResourceLoader implements ModuleComponent
{
	use GetModuleComponent;

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $table_name;

	/**
	 * @var Column[]
	 */
	protected $columns;

	/**
	 * @var Index[]
	 */
	protected $indexes;

	/**
	 * @var Filter[]
	 */
	protected $filters;

	/**
	 * @var Prop
	 */
	protected $extra;

	public function __construct( FileResource $file )
	{
		$this->columns = new Collection();
		$this->indexes = new Collection();
		$this->filters = new Collection();
		$this->load($file);
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public function getTableName(): string
	{
		return $this->table_name;
	}

	/**
	 * @return Column[] | Collection
	 */
	public function getColumns(): Collection
	{
		return $this->columns;
	}

	/**
	 * @return Index[] | Collection
	 */
	public function getIndexes(): Collection
	{
		return $this->indexes;
	}

	/**
	 * @return array[] | Collection
	 */
	public function getFilters(): Collection
	{
		return $this->filters;
	}

	/**
	 * @return Prop
	 */
	public function getExtras(): Prop
	{
		return $this->extra;
	}

	/**
	 * Get extra value
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getExtra(string $name, $default = null)
	{
		return $this->extra->getOr($name, $default);
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

		$this->table_name = $file->get("name");
		$this->setModule($module);

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
		count($indexes) && $this->loadIndexes($indexes);
		count($filters) && $this->loadFilters($filters);

		$this->extra = new Prop( $file->getOr("extra", []) );
	}

	protected function loadPrimaryKeys( array $fields )
	{
		foreach( $fields as $field )
		{
			if( isset($this->columns[$field]) )
			{
				$this->columns[$field]->set("index", true);
				$this->columns[$field]->set("primary", true);
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

			if( isset($this->columns[$name]) )
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
			$unique = in_array("unique", $flag);

			// create full field data

			$properties =
				[
					"type" => $type,
					"subtype" => $subtype,
					"title" => "",
					"not_null" => ! isset($prop["notnull"]) || $prop["notnull"] === true,
					"index" => $unique || in_array("index", $flag),
					"unique" => $unique,
					"primary" => false,
					"auto_increment" => in_array("autoincrement", $flag),
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

			$this->columns[$name] = new Column($name, $properties, new Prop(isset($prop["extra"]) && is_array($prop["extra"]) ? $prop["extra"] : []) );

			if( isset($prop["filter"]) )
			{
				$this->addFilter($name, $prop["filter"]);
			}
		}
	}

	protected function addFilter($name, $filter)
	{
		if( !isset($this->columns[$name]) )
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
				$this->filters[$name] = new Filter($name);
			}

			for( $i = 0; $i < $count; $i++ )
			{
				if( $keys[$i] !== $i )
				{
					$this->filters[$name]->add($filter);
					return;
				}
			}

			for( $i = 0; $i < $count; $i++ )
			{
				$this->filters[$name]->add($filter[$i]);
			}
		}
		else
		{
			if( !isset($this->filters[$name]) )
			{
				$this->filters[$name] = new Filter($name);
			}

			$this->filters[$name]->add($filter);
		}
	}

	protected function loadIndexes( array $indexes )
	{
		static $types = ['INDEX', 'PRIMARY', 'UNIQUE', 'FULLTEXT'];

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
				if( !isset($this->columns[$field]))
				{
					continue;
				}
			}

			$lower = $type !== 'FULLTEXT' ? strtolower($type) : 'index';
			foreach($fields as $field)
			{
				$this->columns[$field][$lower] = true;
				if( $lower !== 'index' )
				{
					$this->columns[$field]['index'] = true;
				}
			}

			$this->indexes[] = new Index(isset($index["name"]) ? (string) $index["name"] : $fields[0], $fields, $type);
		}
	}
}