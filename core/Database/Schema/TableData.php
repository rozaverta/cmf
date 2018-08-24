<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.04.2018
 * Time: 21:09
 */

namespace EApp\Database\Schema;

use EApp\Component\Module;
use EApp\Prop;
use EApp\Support\Collection;
use EApp\Interfaces\TypeOfInterface;
use EApp\Traits\GetModuleComponentTrait;
use EApp\Filesystem\Resource as ResourceFile;
use EApp\Interfaces\ModuleComponentInterface;

class TableData extends Collection implements ModuleComponentInterface, TypeOfInterface
{
	use ExtraTrait;
	use GetModuleComponentTrait;

	/**
	 * @var string
	 */
	protected $table_name = "";

	/**
	 * @var Index[]
	 */
	protected $indexes;

	/**
	 * @var Filter[]
	 */
	protected $filters;

	/** @noinspection All */

	/**
	 * TableData constructor.
	 *
	 * @param string $table_name
	 * @param Module $module
	 * @param Collection $columns
	 * @param Collection $indexes
	 * @param Collection $filters
	 * @param Prop $extra
	 *
	 */
	public function __construct( string $table_name, Module $module, Collection $columns, Collection $indexes, Collection $filters, Prop $extra )
	{
		$this->fill($table_name, $module, $columns, $indexes, $filters, $extra);
	}

	public static function createInstanceFromResource( ResourceFile $file )
	{
		$loader = new ResourceLoader($file);

		return new self(
			$loader->getTableName(),
			$loader->getModule(),
			$loader->getColumns(),
			$loader->getIndexes(),
			$loader->getFilters(),
			$loader->getExtras()
		);
	}

	public static function __set_state( $an_array )
	{
		$ref = new \ReflectionClass(static::class);

		/** @var static $instance */
		$instance = $ref->newInstanceWithoutConstructor();
		$instance->fill(
			$an_array["table_name"],
			$an_array["module"],
			$an_array["items"],
			$an_array["indexes"],
			$an_array["filters"],
			$an_array["extra"]
		);

		return $instance;
	}

	/**
	 * @return \EApp\Database\Query\Builder
	 */
	public function table()
	{
		return \DB::table($this->getTableName());
	}

	/**
	 * GetTrait table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return $this->table_name;
	}

	/**
	 * @return Index[] | Collection
	 */
	public function getIndexes(): Collection
	{
		return $this->indexes;
	}

	/**
	 * @return Filter[] | Collection
	 */
	public function getFilters(): Collection
	{
		return $this->filters;
	}

	/**
	 * GetTrait filter for one column
	 *
	 * @param string $name
	 * @return Filter|null
	 */
	public function getFilter(string $name)
	{
		return isset( $this->filters[$name] ) ? $this->filters[$name] : null;
	}

	public function typeOf( & $value, $name = null ): bool
	{
		return $value instanceof Column && $value->getName() === $name;
	}

	protected function fill(string $table_name, Module $module, $items, Collection $indexes, Collection $filters, Prop $extra)
	{
		$this->table_name = $table_name;
		$this->setModule($module);
		$this->reload($items);
		$this->indexes = $indexes;
		$this->filters = $filters;
		$this->extra = $extra;
	}
}