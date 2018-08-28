<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.09.2017
 * Time: 19:14
 */

namespace EApp\Database\Schema;

use EApp\Filesystem\Resource;
use EApp\Interfaces\PhpExportSerializeInterface;
use EApp\ModuleCore;
use EApp\Exceptions\NotFoundException;
use EApp\Cache;
use EApp\Module\Module;
use EApp\Prop;
use EApp\Support\Collection;
use EApp\Support\PhpExportSerialize;
use EApp\Traits\CacheNameInstanceTrait;
use Serializable;

/**
 * Class SchemaManager
 *
 * @method static Table cache( string $name )
 *
 * @package EApp\DB
 */
class Table extends TableData implements PhpExportSerializeInterface, Serializable
{
	use CacheNameInstanceTrait;

	public function __construct( string $table )
	{
		$row = \DB
			::table("scheme_tables")
			->where("name", $table)
			->first();

		if( ! $row )
		{
			throw new NotFoundException("The '{$table}' table not found in the database");
		}

		$loader = new ResourceLoader(
			new Resource(
				"db_" . $table,
				null,
				$row->module_id > 0 ? new Module( (int) $row->module_id ) : new ModuleCore()
			)
		);

		parent::__construct(
			$loader->getTableName(),
			$loader->getModule(),
			$loader->getColumns(),
			$loader->getIndexes(),
			$loader->getFilters(),
			$loader->getExtras()
		);
	}

	protected function importCacheData( $data )
	{
		$this->fill(
			$data["table_name"],
			Module::cache($data["module_id"]),
			$data["items"],
			new Collection($data["indexes"]),
			new Collection($data["filters"]),
			new Prop($data["extra"])
		);
	}

	protected function exportCacheData()
	{
		return [
			"table_name"    => $this->getTableName(),
			"module_id"     => $this->getModuleId(),
			"items"         => $this->items,
			"indexes"       => $this->getIndexes()->getAll(),
			"filters"       => $this->getFilters()->getAll(),
			"extra"         => $this->extra->getAll()
		];
	}

	protected static function createCache( string $name ): Cache
	{
		return new Cache($name, 'table_schema');
	}

	public function phpExportSerialize(): PhpExportSerialize
	{
		return new PhpExportSerialize(
			static::class, 'cache', [$this->getTableName()]
		);
	}

	/**
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 * @since 5.1.0
	 */
	public function serialize()
	{
		return serialize($this->exportCacheData());
	}

	/**
	 * Constructs the object
	 * @link http://php.net/manual/en/serializable.unserialize.php
	 * @param string $serialized <p>
	 * The string representation of the object.
	 * </p>
	 * @return void
	 * @since 5.1.0
	 */
	public function unserialize( $serialized )
	{
		$this->importCacheData(unserialize($serialized));
	}
}