<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.12.2017
 * Time: 15:03
 */

namespace EApp\SecurityFilter;

use EApp\DB\QueryRecordPrototype;
use EApp\DB\TableSchema;
use EApp\SecurityFilter\Exceptions\ValidateException;
use EApp\Support\Json;

class TableSchemaFilter
{
	/**
	 * @var TableSchema
	 */
	protected $table;

	/**
	 * @var Filter
	 */
	protected $filter;

	protected $field_rules = [];

	public function __construct( TableSchema $table )
	{
		$this->table = $table;
		$this->filter = new Filter();
	}

	public function addFieldRule($name, \Closure $callback)
	{
		if( $this->table->getIs($name) )
		{
			if( !isset($this->field_rules[$name]) )
			{
				$this->field_rules[$name] = [];
			}
			$this->field_rules[$name][] = $callback;
		}
		return $this;
	}

	public function addRule( $name, \Closure $callback )
	{
		$this->filter->addRule($name, $callback);
		return $this;
	}

	public function valid( array $data, array $ignore_fields = [], $data_only = false )
	{
		$result = [];

		foreach( $this->table as $row )
		{
			$name = $row['name'];
			if( in_array($name, $ignore_fields, true) )
			{
				continue;
			}

			if( !isset($data[$name]) )
			{
				if( $data_only )
				{
					continue;
				}
				$data[$name] = isset($row['default']) ? $row['default'] : '';
			}

			$result[$name] = $this->make($data[$name], $row);
		}

		return $result;
	}

	public function difference( array $data, QueryRecordPrototype $record )
	{
		$update = [];

		foreach( $data as $name => $value )
		{
			$row = $this->table->get($name);
			$old = $record->get($name);

			if($row['type'] === 'json')
			{
				$old = Json::stringify($old);
			}

			if( $old !== $value )
			{
				$update[$name] = $value;
			}
		}

		return $update;
	}

	public function filter( $value, $name )
	{
		$row = $this->table->get($name);
		if($row)
		{
			return $this->make($value, $row);
		}

		throw new ValidateException(null, "Unknown field '{$name}'");
	}

	public function format( $value, $name )
	{
		$row = $this->table->get($name);
		if($row)
		{
			return $this->type( $value, $row );
		}

		throw new ValidateException(null, "Unknown field '{$name}'");
	}

	protected function make( $value, $row )
	{
		$name = $row['name'];
		if( isset($this->field_rules[$name]) )
		{
			foreach($this->field_rules[$name] as $callback)
			{
				$value = $callback( $value, $row );
			}
		}

		$flt = $this->table->fieldFilter($name);
		if( $flt )
		{
			$value = $this->filter->valid($value, $flt, isset($row['title']) ? $row['title'] : null);
		}

		// format default

		return $this->type( $value, $row );
	}

	protected function type( $value, $row )
	{
		// format default

		if( $row["type"] === "integer" )
		{
			return (int) $value;
		}

		if( $row["type"] === "number" )
		{
			return (float) $value;
		}

		if( $row["type"] === "boolean" )
		{
			if( is_bool($value) )
			{
				return $value;
			}

			if( is_numeric($value) )
			{
				return $value > 0;
			}
			else if( in_array(strtolower((string) $value), ['yes', 'on', 'true', $row['name']], true) )
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		if( $row["type"] === "json" )
		{
			try {
				$value = TableSchema::toJsonArray($value);
			}
			catch( \InvalidArgumentException $e ) {
				throw new ValidateException(isset($row['title']) ? $row['title'] : null, "Invalid json data");
			}
			return Json::stringify($value);
		}

		return $value;
	}
}
