<?php

namespace EApp\Database;

use EApp\Database\Query\Builder;
use EApp\Database\Schema\Table;
use EApp\Support\CollectionRecorder;
use InvalidArgumentException;
use EApp\Support\Collection;

abstract class QueryPrototype
{
	use QueryTypeTrait;

	/**
	 * @var Query\Builder
	 */
	protected $builder;

	/**
	 * The columns that should be returned.
	 *
	 * @var array
	 */
	protected $columns = [];

	/**
	 * The valid columns.
	 *
	 * @var array
	 */
	protected $valid_columns = [];

	/**
	 * @var Table
	 */
	protected $table_schema;

	protected $total = 0;
	protected $pagination = false;

	protected $wheres = [];
	protected $orders = [];
	protected $limit  = 0;
	protected $offset = 0;

	public function __construct()
	{
		$this->table_schema = Table::cache($this->getTableName());
		$this->initialisation();
	}

	abstract public function getTableName();

	/**
	 * Add a basic where clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return $this
	 */
	public function filter($column, $operator = null, $value = null)
	{
		if( !isset($this->valid_columns[$column]) )
		{
			throw new InvalidArgumentException("Column '{$column}' not used");
		}

		$this->wheres[] = [ $this->valid_columns[$column], $operator, $value ];
		return $this;
	}

	/**
	 * Delete all filters.
	 *
	 * @return $this
	 */
	public function filterReset()
	{
		$this->wheres = [];
		return $this;
	}

	/**
	 * Delete all orders.
	 *
	 * @return $this
	 */
	public function orderReset()
	{
		$this->orders = [];
		return $this;
	}

	/**
	 * Add an "order by" clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $direction
	 * @return $this
	 */
	public function orderBy($column, $direction = 'asc')
	{
		if( !isset($this->valid_columns[$column]) )
		{
			throw new InvalidArgumentException("Column '{$column}' not used");
		}

		$this->orders[] = [$this->valid_columns[$column], $direction];
		return $this;
	}

	/**
	 * Add a descending "order by" clause to the query.
	 *
	 * @param  string  $column
	 * @return $this
	 */
	public function orderByDesc($column)
	{
		return $this->orderBy($column, 'desc');
	}

	/**
	 * Put the query's results in random order.
	 *
	 * @param  string  $seed
	 * @return $this
	 */
	public function inRandomOrder($seed = '')
	{
		$this->orders[] = ["@random", $seed];
		return $this;
	}

	/**
	 * Set the "limit" value of the query.
	 *
	 * @param  int $value
	 * @param  int $offset
	 * @return $this
	 */
	public function limit($value, $offset = null)
	{
		$this->limit = max(0, (int) $value);
		if( !is_null($offset) )
		{
			$this->offset($offset);
		}
		return $this;
	}

	/**
	 * Set the "offset" value of the query.
	 *
	 * @param  int  $value
	 * @return $this
	 */
	public function offset($value)
	{
		$this->offset = max(0, (int) $value);
		return $this;
	}

	/**
	 * Set the limit and offset for a given page.
	 *
	 * @param  int  $page
	 * @param  int  $perPage
	 * @return \EApp\Database\Query\Builder|static
	 */
	public function forPage($page, $perPage = 15)
	{
		if( $page < 1 )
		{
			$page = 1;
		}
		return $this->limit($perPage, ($page - 1) * $perPage);
	}

	/**
	 * Execute the query as a "select" statement and get result as collection.
	 *
	 * @return \EApp\Support\Collection | \EApp\Support\CollectionRecorder
	 */
	public function get()
	{
		$builder = $this->createBuilder();
		if( !$this->prepare($builder) )
		{
			return $this->complete([]);
		}

		return $this->complete(
			$builder->getConnection()->select(
				$builder->toSql(), $builder->getBindings(), ! $builder->useWritePdo, $this->fetchObject()
			)
		);
	}

	protected function createBuilder()
	{
		$builder = Manager::table( $this->table_schema->getTableName() . ' as t' );
		$builder->select(count($this->columns) ? $this->columns : ['t.*']);

		foreach( $this->wheres as $where )
		{
			if( $where[1] === "in" )
			{
				$builder->whereIn($where[0], $where[2]);
			}
			else if( $where[1] === "not in" )
			{
				$builder->whereNotIn($where[0], $where[2]);
			}
			else
			{
				$builder->where( $where[0], $where[1], $where[2] );
			}
		}

		foreach( $this->orders as $order )
		{
			if( $order[0] === '@random' )
			{
				$builder->inRandomOrder($order[1]);
			}
			else
			{
				$builder->orderBy($order[0], $order[1]);
			}
		}

		if( $this->limit > 0 )
		{
			$builder->limit($this->limit, $this->offset);
		}
		else if( $this->offset > 0 )
		{
			$builder->offset($this->offset);
		}

		return $builder;
	}

	// for override editable

	protected function initialisation()
	{
		foreach($this->table_schema as $name => $prop)
		{
			$prefix_name = 't.' . $name;
			$this->columns[] = $prefix_name;
			$this->valid_columns[$name] = $prefix_name;
			if( $this->check_type )
			{
				$this->setType($prop["type"], $name);
			}
		}
	}

	protected function prepare( Builder $builder )
	{
		if( $this->pagination || $this->limit > 0 )
		{
			$this->total = $builder->getCountForPagination( $this->getColumnNameForPagination() );
			if( $this->total < 1 || $this->offset > $this->total )
			{
				return false;
			}
		}

		return true;
	}

	protected function getColumnNameForPagination()
	{
		return 't.id';
	}

	protected function fetchObject()
	{
		return false;
	}

	protected function complete( array $items )
	{
		// use default \stdClass, calculate boolean, number, datetime
		if( $this->use_type )
		{
			foreach($items as $row)
			{
				if( count($this->fields_number) )
					foreach( $this->fields_number as $key )
					{
						$row->{$key} = (float) $row->{$key};
					}

				if( count($this->fields_boolean) )
					foreach( $this->fields_boolean as $key )
					{
						$row->{$key} = $row->{$key} > 0;
					}

				if( count($this->fields_datetime) )
					foreach( $this->fields_datetime as $key )
					{
						$row->{$key} = new \DateTime($row->{$key});
					}
			}
		}

		if( $this->pagination )
		{
			return new CollectionRecorder(
				$items,
				$this->limit,
				$this->offset,
				$this->total
			);
		}
		else
		{
			return new Collection($items);
		}
	}
}