<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 02.04.2018
 * Time: 21:46
 */

namespace EApp\Config\Driver;

use EApp\Component\Module;
use EApp\Config\Scheme\PropertySchemeDesigner;
use EApp\Event\EventManager;
use EApp\ModuleCore;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Json;
use EApp\System\Interfaces\SystemDriver;

class ConfigFactory implements SystemDriver
{
	/**
	 * @var Module
	 */
	protected $module;

	public function __construct( Module $module = null )
	{
		$this->module = is_null($module) ? new ModuleCore() : $module;
	}

	public function getModule()
	{
		return $this->module;
	}

	public function create( $name, array $data, $properties = null )
	{
		$insert = $this->filter(0, $name, $data, $properties);
		$dispatcher = EventManager::dispatcher("onComponentDriverAction");

		$dispatcher
			->dispatch(
				new Events\ConfigCreateEvent($this, $name, $insert)
			);

		\DB::table("config")->insert($insert);

		$dispatcher->complete();

		return $this;
	}

	public function update( $name, array $data, $properties = null )
	{
		if( ! self::isValidName($name) )
		{
			throw new \InvalidArgumentException("Invalid config name '{$name}'");
		}

		$id = $this->getId($name, true);
		if( !$id )
		{
			$title = $this->module->get("title");
			throw new NotFoundException("FileConfig name '{$name}' not found for module '{$title}'");
		}

		$update = $this->filter($id, $name, $data, $properties);
		$dispatcher = EventManager::dispatcher("onComponentDriverAction");

		$dispatcher
			->dispatch(
				new Events\ConfigUpdateEvent($this, $name, $data)
			);

		\DB::table("config")
			->whereId($id)
			->update($update);

		$dispatcher->complete();

		return $this;
	}

	public function replace( $name, array $data, $properties = null )
	{
		if( $this->getId($name, true) > 0 )
		{
			return $this->update($name, $data, $properties);
		}
		else
		{
			return $this->create($name, $data, $properties);
		}
	}

	public function delete( $name )
	{
		$id = $this->getId($name, true);
		if($id < 1)
		{
			return $this;
		}

		$dispatcher = EventManager::dispatcher("onComponentDriverAction");
		$dispatcher
			->dispatch(
				new Events\ConfigDeleteEvent($this, $name)
			);

		\DB::table("config")
			->whereId($id)
			->delete();

		$dispatcher->complete();

		return $this;
	}

	// static functions

	/**
	 * @param $name
	 * @return \EApp\Config\Scheme\PropertySchemeDesigner | bool
	 */
	public static function getConfigItem( $name )
	{
		if( !self::isValidName($name) )
		{
			return false;
		}

		$con = \DB::connection();
		$sql = $con->table("config")
			->where("name", $name)
			->select(["*"])
			->limit(1)
			->toSql();

		$result = $con->select($sql, [], true, PropertySchemeDesigner::class);
		return count($result) ? reset($result) : false;
	}

	/**
	 * Config name is exists
	 *
	 * @param string $name
	 * @param null | int $module_id
	 * @return bool
	 */
	public static function hasName( $name, $module_id = null )
	{
		if( !self::isValidName($name) )
		{
			return false;
		}

		$builder = \DB::table("config")->where("name", $name);
		if( is_numeric($module_id) )
		{
			$builder->where("module_id", (int) $module_id );
		}

		return $builder->count(["id"]) > 0;
	}

	/**
	 * Validate config name
	 *
	 * @param string $name
	 * @return bool
	 */
	public static function isValidName( $name )
	{
		$len = strlen($name);
		if( !$len || ! ctype_alpha($name[0]) || preg_match('/[^0-9a-zA-Z\._]/', $name) )
		{
			return false;
		}

		$close = $name[$len - 1];
		return $len < 256 && $close !== "." && $close !== "_" && strpos($name, "..") === false;
	}

	// protected

	protected function filter( $id, $name, $data, $properties )
	{
		$raw = [];
		$map = ["title", "type", "default_value"];
		$config_name = $name;

		// insert
		if( $id < 1 )
		{
			if( ! self::isValidName($name) )
			{
				throw new \InvalidArgumentException("Invalid config name '{$name}'");
			}
			if( $this->getId($name, false) > 0 )
			{
				throw new \InvalidArgumentException("Duplicate config name '{$name}'");
			}

			foreach($map as $key)
			{
				$raw[$key] = isset($data[$key]) ? trim($data[$key]) : "";
			}

			$raw["name"] = $name;
			$raw["module_id"] = $this->module->get("id");
			$raw["value"] = "";

			if( !is_array($properties) )
			{
				$properties = [];
			}
		}

		//  update
		else
		{
			// rename config name
			if( isset($data["name"]) && $data["name"] !== $name )
			{
				$new_name = $data["name"];
				if( ! self::isValidName($new_name) )
				{
					throw new \InvalidArgumentException("Invalid config name '{$new_name}'");
				}
				if( $this->getId($new_name, false) > 0 )
				{
					throw new \InvalidArgumentException("Duplicate config name '{$new_name}'");
				}
				$config_name = $new_name;
				$raw["name"] = $new_name;
			}

			foreach($map as $key)
			{
				if(isset($data[$key]))
				{
					$raw[$key] = trim($data[$key]);
				}
			}
		}

		if( isset($raw["title"]) && !strlen($raw["title"]) )
		{
			$raw["title"] = "FileConfig for " . $config_name;
		}
		if( is_array($properties) )
		{
			$raw["properties"] = Json::stringify($properties);
		}

		return $raw;
	}

	protected function getId( $name, $for_module )
	{
		if( ! self::isValidName($name) )
		{
			return 0;
		}

		$rs = \DB::table("config")
			->where('name', $name);

		if($for_module)
		{
			$rs->where("module_id", $this->module->get("id") );
		}

		$id = $rs->value("id");
		if( !is_numeric($id) )
		{
			return 0;
		}

		return (int) $id;
	}
}