<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 07.04.2018
 * Time: 18:31
 */

namespace EApp\Component\Driver;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

use EApp\Component\Module;
use EApp\Database\Connection;
use EApp\Database\Schema\Column;
use EApp\Database\Schema\Index;
use EApp\Database\Schema\TableData;
use EApp\Event\EventManager;
use EApp\ModuleCoreConfig;
use EApp\Prop;
use EApp\Interfaces\Loggable;
use EApp\Traits\GetModuleComponentTrait;
use EApp\Traits\LoggableTrait;
use EApp\Filesystem\Resource;
use EApp\Interfaces\SystemDriverInterface;
use EApp\Text;

class DB implements SystemDriverInterface, Loggable
{
	use LoggableTrait;
	use Traits\DBALToolsTraits;
	use Traits\ResourceBackup;
	use GetModuleComponentTrait;

	/**
	 * @var string
	 */
	protected $version;

	protected $table_prefix = null;

	protected $dir_current = "";

	protected $dir_version = "";

	public function __construct( Module $module, $version = null )
	{
		$this->setModule($module);

		// read last version
		if( is_null($version) )
		{
			/** @var \EApp\Component\ModuleConfig $config */

			$config_class = $module->getId() === 0 ? ModuleCoreConfig::class : $module->getNamespace() . "Module";
			$config = new $config_class();
			$version = $config->version;
		}

		$this->version = $version;
		$this->dir_current = APP_DIR . "resource" . DIRECTORY_SEPARATOR;
		$this->dir_version = $this->dir_current . $this->version . DIRECTORY_SEPARATOR;
	}

	public function createTable( $name )
	{
		$module_id = $this->getModule()->getId();

		if( $name === "scheme_tables" && $module_id === 0 )
		{
			if($this->isSchemaTables())
			{
				throw new \InvalidArgumentException("Table '{$name}' has been installed");
			}
		}
		else
		{
			$prop = $this->getTableProperty($name);
			if($prop->get("installed")) {
				throw new \InvalidArgumentException("Table '{$name}' has been installed");
			}
		}

		$this->resourceDirIsWritable($module_id, false, true);

		$fileResource = new Resource( "db_" . $name, null, $this->getModule() );
		$table = TableData::createInstanceFromResource( $fileResource );
		$schema = new Schema();
		$tableDbal = $this->getDbalTable( $table, $schema->createTable( $this->getTablePrefix() . $table->getTableName() ) );

		// call listener
		$dispatcher = EventManager::dispatcher("onComponentDriverAction");
		$dispatcher
			->dispatch(
				new Events\DataBaseCreateTableDriverEvent($this, [
					"resource" => $fileResource,
					"table" => $table,
					"schema" => $tableDbal
				])
			);

		$queries = $schema->toSql( $this->getDoctrineDbalPlatform() );
		$record = [
			"module_id" => $module_id,
			"name" => $name,
			"title" => $fileResource->getIs("title") ? $fileResource->get("title") : "Table {$name}",
			"description" => $fileResource->getOr("description", ""),
			"version" => $this->version
		];

		\DB::connection()
			->transaction(function(Connection $db) use ($queries, $record) {

				foreach($queries as $sql)
				{
					$db->statement($sql);
				}

				$db
					->table("scheme_tables")
					->insert($record);
			});

		// write resources

		$this->resourceWriteFileContent($name, $module_id, $fileResource, $this->version);
		$this->resourceWriteFileContent($name, $module_id, $fileResource);

		$dispatcher->complete();
		$this->addLogDebug(new Text("Add new database table %s", $name));

		return $this;
	}

	public function updateTable( $table_name, $new_table_name = null )
	{
		$prop = $this->getTableProperty($table_name);
		if( !$prop->get("installed")) {
			throw new \InvalidArgumentException("Table '{$table_name}' has been not installed");
		}

		if( !$prop->get("module_compared")) {
			throw new \InvalidArgumentException("Table '{$table_name}' is used by another module");
		}

		if( version_compare($this->version, $prop->get("version"), "<") )
		{
			throw new \InvalidArgumentException("The latest version of the module can not be less than the current version of the module");
		}

		$rename = false;
		if( is_null($new_table_name) || !strlen($new_table_name) )
		{
			$new_table_name = $table_name;
		}
		else if($new_table_name !== $table_name)
		{
			$rename = true;
		}

		$module_id = $this->getModule()->getId();
		$this->resourceDirIsWritable($module_id, false, true);

		$fileCurrent = new Resource("db_" . $table_name, null, $this->getModule(), true );
		$file = new Resource("db_" . $new_table_name, null, $this->getModule() );
		$tableCurrent = TableData::createInstanceFromResource($fileCurrent);
		$table = TableData::createInstanceFromResource($file);

		$tableDbalCurrent = $this->getDbalTable($tableCurrent);
		$tableDbal = $this->getDbalTable($table);
		$comparator = new Comparator();
		$diff = $comparator->diffTable($tableDbalCurrent, $tableDbal);

		// call listener
		$dispatcher = EventManager::dispatcher("onComponentDriverAction");
		$dispatcher
			->dispatch(
				new Events\DataBaseUpdateTableDriverEvent($this, [
					"resource_current" => $fileCurrent,
					"table_current" => $tableCurrent,
					"resource" => $file,
					"table" => $table,
					"diff" => $diff
				])
			);

		$queries = [];
		if( $diff !== false )
		{
			$diffSchema = new SchemaDiff([], [$diff]);
			$queries = $diffSchema->toSql( $this->getDoctrineDbalPlatform() );
		}

		$update = [
			"version" => $this->version
		];

		if( $rename )
		{
			$update["name"] = $new_table_name;
		}

		foreach(["title", "description"] as $key)
		{
			if($file->getIs($key))
			{
				$value = $file->get($key);
				if($value !== $prop->get($key))
				{
					$update[$key] = $file->get($key);
				}
			}
		}

		\DB::connection()
			->transaction(function(Connection $db) use ($queries, $prop, $update) {

				foreach($queries as $sql)
				{
					$db->statement($sql);
				}

				$db
					->table("scheme_tables")
					->whereId($prop->get("id"))
					->update($update);
			});

		// write and remove resources

		$rename && $this->resourceRemoveFile($table_name, $module_id, "#/data_base_table");
		$this->resourceWriteFileContent($new_table_name, $module_id, $file, $this->version);
		$this->resourceWriteFileContent($new_table_name, $module_id, $file);

		// complete

		$dispatcher->complete();
		$this->addLogError(new Text("Update database table '%s'", $table_name), "DEBUG");
		if($rename) {
			$this->addLogError(new Text("Rename data base table '%s' in '%s'", $table_name, $new_table_name), "DEBUG");
		}

		return $this;
	}

	public function dropTable( $name )
	{
		$prop = $this->getTableProperty($name);
		if( !$prop->get("installed")) {
			return $this;
		}

		if( !$prop->get("module_compared")) {
			throw new \InvalidArgumentException("Table '{$name}' is used by another module");
		}

		$fileResource = new Resource( "db_" . $name, null, $this->getModule(), true );
		$table = TableData::createInstanceFromResource( $fileResource );
		$schema = new Schema();
		$schema->dropTable($this->getTablePrefix() . $name);

		// call listener
		$dispatcher = EventManager::dispatcher("onComponentDriverAction");
		$dispatcher
			->dispatch(
				new Events\DataBaseDropTableDriverEvent($this, [
					"resource" => $fileResource,
					"table" => $table
				])
			);

		$queries = $schema->toSql( $this->getDoctrineDbalPlatform() );

		\DB::connection()
			->transaction(function(Connection $db) use ($queries, $prop) {

				foreach($queries as $sql)
				{
					$db->statement($sql);
				}

				$db
					->table("scheme_tables")
					->whereId($prop->get("id"))
					->delete();
			});

		// remove resource file
		$this->resourceRemoveFile($name, $this->getModule()->getId(), "#/data_base_table");

		$dispatcher->complete();
		$this->addLogError(new Text("Drop database table '%s'", $name), "DEBUG");

		return $this;
	}

	protected function getDbalTable( TableData $table, Table $queryTable = null )
	{
		if( is_null($queryTable) )
		{
			$table = new Table( $this->getTablePrefix() . $table->getTableName() );
		}

		$primary = [];

		/** @var Column $column */
		foreach( $table as $column )
		{
			$type = $column->getType();
			$sub_type = $column->getSubtype();
			if( $type !== $sub_type && Type::hasType($sub_type) )
			{
				$type =$sub_type;
			}

			$options = [];

			if(! $column->isNotNull()) $options["notnull"] = false;
			if($column->isUnsigned()) $options["unsigned"] = true;
			if($column->isAutoIncrement()) $options["autoincrement"] = true;
			if($column->isFixed()) $options["fixed"] = true;
			if($column->isDefault()) $options["default"] = $column->getDefault();
			if($column->getLength() > 0) $options["length"] = $column->getLength();
			if($column->getPrecision() > 0) $options["precision"] = $column->getPrecision();
			if($column->getScale() > 0) $options["scale"] = $column->getScale();
			$options["comment"] = $column->getComment();

			$queryTable->addColumn($column->getName(), $type, $options);
			if($column->isPrimary())
			{
				$primary[] = $column->getName();
			}
		}

		// indexes
		/** @var Index $index */
		foreach( $table->getIndexes() as $index )
		{
			$type = $index->getType();

			if( $type === 'PRIMARY' )
			{
				foreach($index->getColumns() as $column)
				{
					$primary[] = $column;
				}
			}
			else if( $type === "UNIQUE" )
			{
				$queryTable->addUniqueIndex($index->getColumns(), $index->getName());
			}
			else
			{
				$flags = [];
				if($type=== "FULLTEXT")
				{
					$flags[] = strtolower($type);
				}
				$queryTable->addIndex($index->getColumns(), $index->getName(), $flags);
			}
		}

		// add primary key
		if(count($primary))
		{
			$queryTable->setPrimaryKey($primary);
		}

		// todo Assign a foreign keys constraint to the table

		return $queryTable;
	}

	/**
	 * @param $name
	 * @return Prop
	 */
	public function getTableProperty( $name )
	{
		$prop = [
			"id" => 0,
			"module_id" => 0,
			"module_compared" => false,
			"name" => $name,
			"title" => "Table " . $name,
			"description" => "",
			"version" => "1.0",
			"installed" => false
		];

		$row = \DB::table("scheme_tables")
			->where("name", $name)
			->first();

		if( $row )
		{
			$prop["id"] = (int) $row->id;
			$prop["module_id"] = (int) $row->module_id;
			$prop["module_compared"] = $prop["module_id"] === $this->getModule()->getId();
			$prop["title"] = $row->title;
			$prop["description"] = $row->description;
			$prop["version"] = $row->version;
			$prop["installed"] = true;
		}

		return new Prop($prop);
	}

	//

	protected function isSchemaTables()
	{
		$table = $this->getTablePrefix() . "scheme_tables";
		$all = \DB::connection()->select( $this->getDoctrineDbalPlatform()->getListTablesSQL() );

		foreach($all as $row)
		{
			$row = (array) $row;
			if( reset($row) === $table )
			{
				return true;
			}
		}

		return false;
	}

	protected function getTablePrefix()
	{
		if( is_null($this->table_prefix))
		{
			$this->table_prefix = \DB::connection()->getTablePrefix();
		}

		return $this->table_prefix;
	}
}