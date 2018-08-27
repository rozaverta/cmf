<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 12:42
 */

namespace EApp\Component\Driver;

use EApp\Component\ModuleFake;
use EApp\Component\ModuleConfig;
use EApp\Schemes\ModulesSchemeDesigner;
use EApp\Database\Connection;
use EApp\Event\EventManager;
use EApp\Filesystem\Resource as JsonResource;
use EApp\Prop;
use EApp\Exceptions\NotFoundException;
use EApp\Interfaces\Loggable;
use EApp\Traits\GetModuleComponentTrait;
use EApp\Traits\LoggableTrait;
use EApp\Interfaces\SystemDriverInterface;
use EApp\Support\Str;
use EApp\Text;

class ModuleRegisterDriverInterface implements SystemDriverInterface, Loggable
{
	use GetModuleComponentTrait;
	use LoggableTrait;

	private $name_space;

	public function __construct( string $name_space )
	{
		$this->name_space = trim($name_space, "\\") . "\\";
	}

	/**
	 * @return Prop
	 * @throws NotFoundException
	 */
	public function register()
	{
		$manifest = $this->getManifest();
		$module_name = $this->getModuleName($manifest);

		// check module registered

		$num = \DB
			::table("modules")
				->where("name", $module_name)
				->count("id") > 0;

		if( $num > 0 )
		{
			throw new \InvalidArgumentException("Module '{$module_name}' is already registered");
		}

		$event = new Events\ModuleRegisterDriverEvent($this, $module_name, $this->name_space, $manifest);
		$dispatcher = EventManager::dispatcher($event->getName());
		$dispatcher
			->dispatch(
				$event
			);

		$prop = new Prop([
			"action" => "register",
			"name" => $module_name,
			"name_space" => $this->name_space,
			"version" => $manifest->getOr("version", "1.0.0")
		]);

		\DB::connection()->transaction(function (Connection $con) use ($prop) {

			$id = $con
				->table("modules")
				->insertGetId([
					"name" => $prop->get("name"),
					"name_space" => $prop->get("name_space"),
					"install" => false,
					"version" => $prop->get("version")
				]);

			if($id < 1)
			{
				throw new \InvalidArgumentException("Can not ready module identifier from database");
			}

			$prop->set("id", $id);
		});

		$module = new ModuleFake( $prop->get("id") );
		$this->setModule($module);
		$prop->set("module", $module);

		$this->addLogDebug(Text::createInstance("%s Module was successfully registered from the %s namespace", $module_name, $this->name_space));
		$dispatcher->complete($prop);
		return $prop;
	}

	public function unregister()
	{
		$module_name = $this->getModuleName($this->getManifest());
		$prop = new Prop([
			"action" => "unregister",
			"unregistered" => false
		]);

		/** @var \EApp\Schemes\ModulesSchemeDesigner $scheme */
		$scheme = \DB
			::table("modules")
				->where("name", $module_name)
				->setResultClass(ModulesSchemeDesigner::class)
				->first();

		if( ! $scheme )
		{
			return $prop;
		}

		if( $scheme->install )
		{
			throw new \InvalidArgumentException("You must uninstall the '{$module_name}' module before cancellation of registration");
		}

		$id = $scheme->id;
		$module = new ModuleFake($id);
		$this->setModule($module);

		$event = new Events\ModuleUnregisterDriverEvent($this, $module);
		$dispatcher = EventManager::dispatcher($event->getName());
		$dispatcher
			->dispatch(
				$event
			);

		$prop->set(
			"unregistered",
			\DB::table("modules")
				->whereId($id)
				->delete() > 0
		);

		$this->unsetModule();

		$this->addLogDebug(Text::createInstance("%s Module was successfully deactivated", $module_name));
		$dispatcher->complete($prop);
		return $prop;
	}

	private function getManifest(): JsonResource
	{
		// check module config exists

		$name_space = $this->name_space;

		$config_class_name = $name_space . "Module";
		if( !class_exists($config_class_name, true) )
		{
			throw new NotFoundException("Module config file for the '{$name_space}' namespace not found");
		}

		$ref = new \ReflectionClass($config_class_name);
		if( ! $ref->isSubclassOf(ModuleConfig::class) )
		{
			throw new NotFoundException($ref->getName() . " must be inherited of " . ModuleConfig::class);
		}

		// load manifest

		$manifest = new JsonResource("manifest", dirname($ref->getFileName()) . DIRECTORY_SEPARATOR . "resources");
		if( $manifest->getType() !== "#/module" )
		{
			throw new \InvalidArgumentException("Invalid manifest type");
		}

		return $manifest;
	}

	private function getModuleName( JsonResource $manifest ): string
	{
		// check module name

		$module_name = $manifest->get("name");
		if( !$module_name )
		{
			$pos = strrpos($this->name_space, "\\");
			$module_name = $pos !== false ? substr($this->name_space, $pos + 1) : "";
		}
		else
		{
			$module_name = Str::studly( (string) $module_name );
		}

		$len = strlen($module_name);
		if( ! $len || $len > 100 || ! preg_match('/^[A-Z][A-Za-z0-9]+$/', $module_name) )
		{
			throw new \InvalidArgumentException("Invalid module name '{$module_name}'");
		}

		return $module_name;
	}
}