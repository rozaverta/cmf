<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.09.2017
 * Time: 19:14
 */

namespace EApp\Database\Schema;

/**
 * Class SchemaManager
 *
 * @package EApp\DB
 */
class Filter
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $filters;

	public function __construct( string $name, array $filters = [] )
	{
		$this->name = $name;
		$this->filters = $filters;
	}

	public function __set_state(array $data)
	{
		if( !isset($data["name"]) )
		{
			throw new \InvalidArgumentException(__CLASS__ . "::" . __METHOD__ . " 'name' property is not used");
		}

		return new static( $data["name"], $data["columns"] ?? [] );
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param $filter
	 * @return $this
	 */
	public function add( $filter )
	{
		$this->filters[] = $filter;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getFilters(): array
	{
		return $this->filters;
	}
}