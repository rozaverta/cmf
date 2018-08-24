<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.04.2018
 * Time: 2:49
 */

namespace EApp\Component\Driver;

use EApp\Database\Connection;
use EApp\Database\Query\Expression;
use EApp\Event\Dispatcher;
use EApp\Event\Driver\EventCallbackDriverInterface;
use EApp\Event\Driver\EventDriverInterface;
use EApp\Interfaces\Loggable;
use EApp\Traits\GetModuleComponentTrait;
use EApp\Traits\LoggableTrait;
use EApp\Interfaces\SystemDriverInterface;
use EApp\Filesystem\Resource;
use EApp\Exceptions\NotFoundException;
use EApp\Text;
use InvalidArgumentException;

abstract class ModuleComponentAbstract implements SystemDriverInterface, Loggable
{
	use Traits\DBALToolsTraits;
	use Traits\ResourceBackup;
	use LoggableTrait;
	use GetModuleComponentTrait;

	protected $module_data = [];

	protected $id = 0;

	/**
	 * @var string
	 */
	protected $name;

	protected $key;

	protected $install = false;

	protected $version = "0.-install";

	protected $name_space = "";

	/**
	 * @var null | \EApp\Filesystem\Resource
	 */
	protected $manifest = null;

	public function getName()
	{
		return $this->name;
	}

	public function getKey()
	{
		return $this->key;
	}

	public function getNameSpace()
	{
		return $this->name_space;
	}

	public function getVersion()
	{
		return $this->version;
	}

	public function hasInstall()
	{
		return $this->install;
	}

	public function hasAdded()
	{
		return strlen($this->name_space) > 0;
	}

	abstract public function install();

	abstract public function update();

	abstract public function uninstall();

	/**
	 * @param null|string $name_space
	 * @return \EApp\Filesystem\Resource
	 * @throws \EApp\Exceptions\NotFoundException | \InvalidArgumentException
	 */
	protected function getManifest( $name_space = null )
	{
		if( is_null($this->manifest) )
		{
			if( !$name_space )
			{
				$name_space = $this->name_space;
			}

			$config_class = $name_space . 'Module';
			if( $this->name === "@core" )
			{
				$config_class .= "CoreConfig";
			}

			if( !class_exists($config_class, true) )
			{
				throw new NotFoundException("Module '{$this->name}' not found");
			}

			$reflector = new \ReflectionClass($config_class);
			$reflector->getFileName();
			$path = rtrim(dirname($reflector->getFileName()), DIRECTORY_SEPARATOR);
			if( !$path )
			{
				throw new InvalidArgumentException("Can not ready base module directory");
			}

			$this->manifest = new Resource("manifest", $path . DIRECTORY_SEPARATOR . "resources");
		}

		return $this->manifest;
	}

	protected function addManifestListeners( Dispatcher $dispatcher )
	{
		$manifest = $this->getManifest();
		$key = '@module:' . $manifest->getPathname();

		$dispatcher->register(function (Dispatcher $dispatcher) use ($manifest) {

			// 0. event listeners
			$rec = $manifest->getOr("listeners", []);
			if(count($rec))
			{
				$module = $this->getModule();

				foreach($rec as $class_name)
				{
					if( class_exists($class_name, true) )
					{
						$listener = new $class_name($module);
						if( method_exists($listener, "dispatch") )
						{
							$dispatcher->listen(function (...$args) use ($listener) {
								$listener->dispatch(...$args);
							});
						}
						else if( method_exists($listener, "__invoke") )
						{
							$dispatcher->listen(function (...$args) use ($listener) {
								$listener(...$args);
							});
						}
						else if( method_exists($listener, "getListenerClosure") )
						{
							$closure = $listener->getListenerClosure();
							if($closure instanceof \Closure)
							{
								$dispatcher->listen($closure);
							}
						}
						else
						{
							$this->addLogError(Text::createInstance("Not found method invoke or dispatch for %s class", $class_name));
						}
					}
				}
			}

		}, $key);
	}

	protected function installData()
	{
		$manifest = $this->getManifest();
		$dir = $manifest->getPath();
		$module = $this->getModule();
		$version = $manifest->get("version");

		// check and make if not exists backup directory
		$this->resourceDirIsWritable($module->getId(), false, true);

		// 1. database tables

		$rec = $manifest->getOr("database_tables", []);
		if(count($rec))
		{
			$db = new DB($module, $version);
			$db->addLogTransport($this);
			foreach($rec as $db_table)
			{
				$db->createTable($db_table);
			}
		}

		// 2. database records

		$rec = $manifest->getOr("database_records", []);
		if(count($rec))
		{
			foreach($rec as $db_table => $items)
			{
				if( !is_array($items) )
				{
					$this->addLogError("Invalid insert database data format in the manifest file");
					continue;
				}
				if( !count($items) )
				{
					continue;
				}

				if( key($items) !== 0 )
				{
					$items = [$items];
				}

				try {
					\DB::connection()->transaction(function ( Connection $connect ) use ($db_table, $items) {
						$builder = $connect->table($db_table);
						foreach($items as $row)
						{
							$builder->insert($this->replaceModuleData((array) $row));
						}
					});

					$this->addLogError(Text::createInstance("Add '%s' record(s) for database table '%s'", count($items), $db_table), "DEBUG");
				}
				catch( \Exception $e )
				{
					$this->addLogError(Text::createInstance("Error database insert records, report: %s", $e->getMessage()));
				}
			}
		}

		// database values
		$rec = $this->getResourceData("database_values", $dir, "#/database_values");
		if(count($rec))
		{
			foreach($rec as $row)
			{
				if( isset($row["table"], $row["values"]) )
				{
					$this->databaseInsertValues((string) $row["table"], (array) $row["values"]);
				}
			}
		}

		// 3. events
		$rec = $this->getResourceData("events", $dir, "#/event_collection");
		if(count($rec))
		{
			$drv = new EventDriverInterface($module);
			$drv->addLogTransport($this);
			foreach($rec as $event)
			{
				if( !is_array($event) )
				{
					$event = ["name" => (string) $event];
				}
				try {
					$drv->create(
						$event["name"],
						$event["title"] ?? "",
						$event["completable"] ?? false
					);
				}
				catch( \Exception $e )
				{
					$this->addLogError(Text::createInstance("Create event %s error, %s", $event["name"], $e->getMessage()));
				}
			}

			unset($drv);
		}

		// events link
		$rec = $manifest->getOr("events", []);
		if(count($rec))
		{
			$drv = new EventCallbackDriverInterface($module);
			$drv->addLogTransport($this);
			foreach( $rec as $class_name => $events )
			{
				if( !is_array($events) )
				{
					$events = [(string) $events];
				}

				$callback = $drv->getCallbackItem($class_name);
				if( !$callback )
				{
					$this->addLogError(Text::createInstance("Event class name %s not found", $class_name));
					continue;
				}

				foreach($events as $event)
				{
					try {
						$drv->link($event, $callback);
					}
					catch( \Exception $e )
					{
						$this->addLogError(Text::createInstance("Callback link error, %s", $e->getMessage()));
					}
				}
			}

			unset($drv);
		}

		// 4. plugins
		$rec = $this->getResourceData("plugins", $dir, "#/plugin_collection");
		if(count($rec))
		{
			// todo
		}

		// 5. templates
		$rec = $this->getResourceData("templates", $dir, "#/template_collection");
		if(count($rec))
		{
			// todo
		}

		// 6. routes
		$rec = $this->getResourceData("routes", $dir, "#/module_route_collection");
		if(count($rec))
		{
			$router = new ModuleRouter($module);
			$router->addLogTransport($this);
			foreach($rec as $item)
			{
				if( !is_array($item))
				{
					$item = ["path" => (string) $item];
				}

				try {
					$router->add(
						isset($item["path"]) ? $item["path"] : "",
						isset($item["type"]) ? $item["type"] : null,
						isset($item["position"]) && is_int($item["position"]) ? $item["position"] : null,
						isset($item["properties"]) && is_array($item["properties"]) ? $item["properties"] : []
					);
				}
				catch(\Exception $e)
				{
					$this->addLogError(Text::createInstance("Can not add mount point, %s", $e->getMessage()));
				}
			}
		}

		// 7. file configs
		$rec = $this->getResourceData("file_configs", $dir, "#/file_config_collection");
		if(count($rec))
		{
			foreach($rec as $name => $data)
			{
				if( !is_array($data) )
				{
					$this->addLogError("Invalid config file data format");
					continue;
				}

				$drv = new ConfigFile($name, $module);
				if( $drv->fileExists() )
				{
					$this->addLogError(Text::createInstance("Can not duplicate the %s config file of the %s module", $name, $module->getName()));
					continue;
				}
				else
				{
					$drv->addLogTransport($this)
						->merge($data)
						->write();
				}

				unset($drv);
			}
		}

		$this->resourceWriteFileContent("manifest", $module->getId(), $manifest, $version);
		$this->resourceWriteFileContent("manifest", $module->getId(), $manifest);

		// todo add cache clean
	}

	protected function updateData()
	{
		//
	}

	protected function uninstallData()
	{
		//
	}

	protected function getResourceData( $name, $dir, $type, $key = "items" )
	{
		$result = [];

		try {
			$rec = new Resource($name, $dir);
			if($rec->getType() !== $type)
			{
				throw new \InvalidArgumentException("Invalid resource type ({$name})");
			}
			$result = $rec->getOr($key, []);
		}
		catch(NotFoundException $e) {}

		return $result;
	}

	protected function setModuleData()
	{
		$manifest = $this->getManifest();

		$this->module_data["module_id"] = 0;
		$this->module_data["module_name"] = $manifest->get("name");
		$this->module_data["module_title"] = $manifest->get("title");
		$this->module_data["module_description"] = $manifest->get("description");
		$this->module_data["module_version"] = $manifest->get("version");

		$time = time();
		$platform = $this->getDoctrineDbalPlatform();

		$this->module_data["tm_timestamp"] = $time;
		$this->module_data["tm_date"] = date($platform->getDateFormatString(), $time);
		$this->module_data["tm_time"] = date($platform->getTimeFormatString(), $time);
		$this->module_data["tm_datetime"] = date($platform->getDateTimeFormatString(), $time);
		$this->module_data["tm_now"] = new Expression($platform->getNowExpression());
	}

	protected function replaceModuleData( array $row )
	{
		foreach(array_keys($row) as $key)
		{
			if( is_string($row[$key]) )
			{
				$row[$key] = $this->replaceModuleDataText($row[$key]);
			}
		}

		return $row;
	}

	protected function replaceModuleDataText( $string )
	{
		$pos = strpos($string, '{');

		if( $pos !== false )
		{
			$len = strlen($string);

			// raw $value = "{var_name}"
			if($pos === 0 && $string[$len-1] === "}")
			{
				$name = substr($string, 1, $len-2);
				if( isset($this->module_data[$name]) )
				{
					return $this->module_data[$name];
				}
			}

			// string "{var_name} ... text {var_name2}"
			return preg_replace_callback('/\{([a-z0-9_]+)\}/', function($m) {
				return isset($this->module_data[$m[1]]) ? (string) $this->module_data[$m[1]] : $m[0];
			}, $string);
		}

		return $string;
	}

	protected function databaseInsertValues(string $table, array $values)
	{
		$build = \DB::table($table);
		foreach($values as $insert)
		{
			$insert = $this->replaceModuleData($insert);
			try {
				$build->insert($insert);
			}
			catch(\Exception $e)
			{
				$this->addLogError(Text::createInstance("Database insert error. Table %s, error - %s", $table, $e->getMessage()));
			}
		}
	}
}