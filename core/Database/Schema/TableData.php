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
use EApp\Support\Interfaces\TypeOfInterface;
use EApp\Support\Traits\GetModuleComponent;
use EApp\System\Fs\FileResource;
use EApp\System\Interfaces\ModuleComponent;

class TableData extends Collection implements ModuleComponent, TypeOfInterface
{
	use ExtraTrait;
	use GetModuleComponent;

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

	public function __construct( string $table_name, Module $module, Collection $columns, Collection $indexes, Collection $filters, Prop $extra )
	{
		parent::__construct($columns);

		$this->setModule($module);
		$this->table_name = $table_name;
		$this->indexes = $indexes;
		$this->filters = $filters;
		$this->extra = $extra;
	}

	public static function createInstanceFromResource( FileResource $file )
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

	public function __set_state( $an_array )
	{
		return new self(
			$an_array["table_name"],
			$an_array["module"],
			$an_array["columns"],
			$an_array["indexes"],
			$an_array["filters"],
			$an_array["extra"]
		);
	}

	/**
	 * @return \EApp\Database\Query\Builder
	 */
	public function table()
	{
		return \DB::table($this->getTableName());
	}

	/**
	 * Get table name
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
	public function getIndexes(): array
	{
		return $this->indexes;
	}

	/**
	 * @return Filter[] | Collection
	 */
	public function getFilters(): array
	{
		return $this->filters;
	}

	/**
	 * Get filter for one column
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
}