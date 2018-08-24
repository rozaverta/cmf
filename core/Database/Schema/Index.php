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
class Index
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $columns;

	/**
	 * @var string
	 */
	protected $type;

	public function __construct( string $name, array $columns, string $type = "INDEX" )
	{
		$this->name  = $name;
		$this->columns = $columns;
		$this->type = $type;
	}

	public static function __set_state(array $data)
	{
		if( !isset($data["name"]) )
		{
			throw new \InvalidArgumentException(__CLASS__ . "::" . __METHOD__ . " 'name' property is not used");
		}

		return new static(
			$data["name"],
			$data["columns"] ?? [],
			$data["type"] ?? "INDEX"
		);
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return array
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}
}