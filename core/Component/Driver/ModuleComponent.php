<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.04.2018
 * Time: 2:49
 */

namespace EApp\Component\Driver;

use EApp\Component\Driver\Tools\ModuleFake;
use EApp\Database\Connection;
use EApp\Event\EventManager;
use EApp\Support\Str;
use InvalidArgumentException;

class ModuleComponent extends ModuleComponentAbstract
{
	public function __construct( $name )
	{
		$row = \DB::table("modules")
			->where("name", $name)
			->first();

		if( $row )
		{
			$this->id = (int) $row->id;
			$this->install = $row->install > 0;
			$this->version = $row->version;
			$this->name_space = empty($row->name_space) ? "MD\\{$name}\\" : $row->name_space;
		}
		else if( ! self::isValidName($name) )
		{
			throw new \InvalidArgumentException("Invalid module name '{$name}'");
		}

		$this->name = $name;
		$this->key = Str::cache($this->name, "snake");
	}

	public static function isValidName($name)
	{
		$len = strlen($name);
		return $len > 2 && $len < 256 && strlen($name) !== 'core' && ctype_upper($name[0]) && ctype_alnum($name);
	}

	/**
	 * @return \EApp\Component\Module
	 * @throws \Exception
	 */
	public function getModule()
	{
		if( is_null($this->module) )
		{
			if($this->id > 0)
			{
				$this->module = new ModuleFake($this->id);
			}
			else
			{
				throw new \Exception("Module is not initialised");
			}
		}
		return $this->module;
	}

	public function add( $name_space = null )
	{
		if( !$name_space )
		{
			$name_space = "MD\\" . $this->name;
		}

		$name_space = trim($name_space, "\\") . "\\";
		if( $this->hasAdded() )
		{
			if( $this->name_space !== $name_space )
			{
				throw new InvalidArgumentException("Namespaces do not match");
			}
			return $this;
		}

		$manifest = $this->getManifest($name_space);

		$dispatcher = EventManager::dispatcher("onComponentDriverAction");
		$this->addManifestListeners($dispatcher);

		$dispatcher
			->dispatch(
				new Events\ModuleAddDriverEvent($this, [
					"manifest" => $manifest
				])
			);

		\DB::connection()->transaction(function (Connection $con) use ($name_space, $manifest) {

			$version = $manifest->get("version");
			$id = $con->table("modules")
				->insertGetId([
					"name" => $this->name,
					"name_space" => $name_space === "MD\\{$this->name}\\" ? "" : $name_space,
					"install" => false,
					"version" => $version
				]);

			if($id < 1)
			{
				throw new \InvalidArgumentException("Can not ready module identifier from database");
			}

			$this->id = $id;
			$this->version = $version;
		});

		$dispatcher->complete($this->id);

		return $this;
	}

	public function install()
	{
		if( $this->install )
		{
			throw new \InvalidArgumentException("The module has already been installed");
		}
		if( !$this->hasAdded() )
		{
			throw new \InvalidArgumentException("The module was not added to the database");
		}

		$this->module_data = []; // clean module data
		$manifest = $this->getManifest();

		$dispatcher = EventManager::dispatcher("onComponentDriverAction");
		$this->addManifestListeners($dispatcher);

		$dispatcher
			->dispatch(
				new Events\ModuleInstallDriverEvent($this, [
					"id" => $this->id,
					"manifest" => $manifest
				]), function ($result) {
					if( is_array($result) ) {
						$this->module_data = array_merge($this->module_data, $result);
					}
				}
			);

		$dispatcherClone = $dispatcher->getCompletableClone();

		$this->setModuleData();
		$this->installData();

		\DB::table("modules")
			->whereId($this->id)
			->update([
				"version" => $this->manifest->get("version"),
				"install" => true
			]);

		$this->install = true;

		$dispatcherClone->complete($this->id);

		return $this;
	}

	public function update()
	{
		//
	}

	public function remove()
	{
		//
	}
}
