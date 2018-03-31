<?php

namespace EApp\DB;

use EApp\DB\Query\Builder;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Interfaces\Arrayable;
use EApp\Support\Traits\Get;

abstract class QueryRecordPrototype implements Arrayable
{
	use QueryTypeTrait;
	use Get;

	protected $items = [];

	/**
	 * The columns that should be returned.
	 *
	 * @var array
	 */
	protected $columns = [];

	/**
	 * The valid assoc columns.
	 *
	 * @var array
	 */
	protected $filter_columns = [];

	/**
	 * Original object.
	 *
	 * @var object				"width" => (int) $image->width,

	 */
	protected $result;

	/**
	 * @var TableSchema
	 */
	protected $table_schema;

	protected $total = 0;
	protected $pagination = false;

	protected $id;
	protected $id_field_name = 'id';

	public function __construct( $id )
	{
		$this->id = (int) $id;
		$this->table_schema = TableSchema::cache($this->getTableName());
		$table = $this->table_schema->getTableName();

		// init fields & data info
		$this->initialisation();

		$builder = $this->createBuilder();
		if( !$this->prepare($builder) )
		{
			throw new \InvalidArgumentException("Can't ready table '{$table}' record.");
		}

		$fetchObject = $this->fetchObject();
		$row = $builder->getConnection()->selectOne( $builder->toSql(), $builder->getBindings(), ! $builder->useWritePdo, $fetchObject );
		if( !$row )
		{
			throw new NotFoundException("Record '{$this->id}' not found.");
		}

		$this->result = $this->complete( $row );

		if( !count($this->filter_columns) )
		{
			$this->items = get_object_vars($this->result);
		}
		else if( $this->result instanceof Arrayable )
		{
			$this->items = $this->result->toArray();
		}
		else
		{
			foreach($this->filter_columns as $name)
			{
				$this->items[$name] = $this->result->{$name};
			}
		}
	}

	abstract public function getTableName();

	/**
	 * Raw result object.
	 *
	 * @return  object
	 */
	public function getRaw()
	{
		return $this->result;
	}

	public function toArray()
	{
		return $this->items;
	}

	protected function createBuilder()
	{
		$builder = Manager::table( $this->table_schema->getTableName() . ' as t' );
		$builder
			->select(count($this->columns) ? $this->columns : ['t.*'])
			->limit(1)
			->whereId( $this->id, $this->getIdentifierColumnName() );

		return $builder;
	}

	// for override editable

	protected function initialisation()
	{
		foreach($this->table_schema as $name => $prop)
		{
			$this->columns[] = 't.' . $name;
			$this->filter_columns[] = $name;
			if( $this->check_type )
			{
				$this->setType($prop["type"], $name);
			}
		}
	}

	protected function prepare( Builder $builder )
	{
		return true;
	}

	protected function getIdentifierColumnName()
	{
		return 't.id';
	}

	protected function fetchObject()
	{
		return false;
	}

	protected function complete( $row )
	{
		// use default \stdClass, calculate boolean, number, datetime
		if( $this->use_type )
		{
			if(count($this->fields_number))
				foreach( $this->fields_number as $key )
				{
					$row->{$key} = $row->{$key} % 1 === 0 ? (int) $row->{$key} : (float) $row->{$key};
				}

			if(count($this->fields_boolean))
				foreach( $this->fields_boolean as $key )
				{
					$row->{$key} = $row->{$key} > 0;
				}

			if(count($this->fields_datetime))
				foreach( $this->fields_datetime as $key )
				{
					$row->{$key} = new \DateTime($row->{$key});
				}
		}

		return $row;
	}
}