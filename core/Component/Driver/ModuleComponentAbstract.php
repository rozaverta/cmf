<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.04.2018
 * Time: 2:49
 */

namespace EApp\Component\Driver;

use EApp\Component\Driver\Tools\FileConfig;
use EApp\Config\Driver\ConfigFactory;
use EApp\Database\Connection;
use EApp\Database\Query\Expression;
use EApp\Event\Dispatcher;
use EApp\Event\Driver\EventCallback;
use EApp\Event\Driver\EventFactory;
use EApp\Support\Interfaces\Loggable;
use EApp\Support\Traits\LoggableTrait;
use EApp\System\Interfaces\SystemDriver;
use EApp\System\Fs\FileResource;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Text;
use InvalidArgumentException;

abstract class ModuleComponentAbstract implements SystemDriver, Loggable
{
	use Traits\DBALToolsTraits;
	use Traits\ResourceBackup;
	use LoggableTrait;

	/**
	 * @var \EApp\Component\Module
	 */
	protected $module = null;

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
	 * @var null | \EApp\System\Fs\FileResource
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

	abstract public function remove();

	/**
	 * @param null|string $name_space
	 * @return \EApp\System\Fs\FileResource
	 * @throws \EApp\Support\Exceptions\NotFoundException | \InvalidArgumentException
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
			if( !class_exists($config_class, true) )
			{
				throw new NotFoundException("Module '{$this->name}' not found.");
			}

			$reflector = new \ReflectionClass($config_class);
			$reflector->getFileName();
			$path = rtrim(dirname($reflector->getFileName()), DIRECTORY_SEPARATOR);
			if( !$path )
			{
				throw new InvalidArgumentException("Can not ready base module directory");
			}

			$this->manifest = new FileResource("manifest", $path . DIRECTORY_SEPARATOR . "resource");
		}

		return $this->manifest;
	}

	protected function addManifestListeners( Dispatcher $dispatcher )
	{
		$manifest = $this->getManifest();
		$key = '@module:' . $manifest->getFile();

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
							$this->addLogError(new Text("Not found method invoke or dispatch for %s class", $class_name));
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

					$this->addLogError(new Text("Add '%s' record(s) for database table '%s'", count($items), $db_table), "DEBUG");
				}
				catch( \Exception $e )
				{
					$this->addLogError(new Text("Error database insert records, report: %s", $e->getMessage()));
				}
			}
		}

		// 3. events
		$rec = $this->getResourceData("events", $dir, "#/event_collection");
		if(count($rec))
		{
			$drv = new EventFactory($module);
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
						isset($event["title"]) ? $event["title"] : null,
						isset($event["completable"]) ? $event["completable"] : false
					);
				}
				catch( \Exception $e )
				{
					$this->addLogError(new Text("Create event error, %s", $e->getMessage()));
				}
			}

			unset($drv);
		}

		// events link
		$rec = $manifest->getOr("events", []);
		if(count($rec))
		{
			$drv = new EventCallback($module);
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
					$this->addLogError(new Text("Event class name %s not found", $class_name));
					continue;
				}

				foreach($events as $event)
				{
					try {
						$drv->link($event, $callback);
					}
					catch( \Exception $e )
					{
						$this->addLogError(new Text("Callback link error, %s", $e->getMessage()));
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
					$this->addLogError(new Text("Can not add mount point, %s", $e->getMessage()));
				}
			}
		}

		// 7. configs
		$rec = $this->getResourceData("configs", $dir, "#/config_collection");
		if(count($rec))
		{
			$cf = new ConfigFactory($module);
			foreach($rec as $name => $data)
			{
				$properties = null;
				if(isset($data["properties"]))
				{
					if( is_array($data["properties"]) )
					{
						$properties = $data["properties"];
					}
					unset($data["properties"]);
				}

				try {
					$cf->create($name, $data, $properties);
				}
				catch(\Exception $e)
				{
					$this->addLogError(new Text("Can not add config record, %s", $e->getMessage()));
				}
			}
		}

		// 8. file configs
		$rec = $this->getResourceData("file_configs", $dir, "#/file_config_collection");
		if(count($rec))
		{
			$dir = $module->getId() < 1 ? null : $this->getKey();
			foreach($rec as $name => $data)
			{
				if( !is_array($data) )
				{
					$this->addLogError("Invalid config file data format");
					continue;
				}

				$config = new FileConfig($name, $dir);
				$key = ($dir ? $dir . "/" : "") . $name;
				if($config->fileExists())
				{
					$this->addLogError(new Text("Can not duplicate config file '%s'", $key));
				}
				else if( !$config->set($data)->write() )
				{
					$this->addLogError(new Text("Can not write config file '%s'", $key));
				}
				else
				{
					$this->addLogError(new Text("Add new config file '%s'", $key), "DEBUG");
				}
			}
		}

		$this->resourceWriteFileContent("manifest", $module->getId(), $manifest, $version);
		$this->resourceWriteFileContent("manifest", $module->getId(), $manifest);
	}

	protected function updateData()
	{
		//
	}

	protected function removeData()
	{
		//
	}

	protected function getResourceData( $name, $dir, $type, $key = "items" )
	{
		$result = [];

		try {
			$rec = new FileResource($name, $dir);
			if($rec->getType() !== $type)
			{
				throw new \IntlException("Invalid resource type ({$name})");
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
			if( is_string($key) )
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
}
