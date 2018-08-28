<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.04.2018
 * Time: 2:49
 */

namespace EApp\Module\Driver;

use EApp\Module\ModuleFake;
use EApp\Module\Module;
use EApp\Schemes\ModulesSchemeDesigner;
use EApp\Event\EventManager;
use EApp\Exceptions\NotFoundException;
use EApp\Support\Str;

class ModuleComponent extends ModuleComponentAbstract
{
	public function __construct( $name )
	{
		/** @var ModulesSchemeDesigner $row */
		$row = \DB
			::table("modules")
				->where("name", Str::studly($name))
				->setResultClass(ModulesSchemeDesigner::class)
				->first();

		if( ! $row )
		{
			throw new NotFoundException("Module '{$name}' not found");
		}

		$this->id = $row->id;
		$this->install = $row->install;
		$this->version = $row->version;
		$this->name_space = $row->name_space;
		$this->name = $row->name;
		$this->key = $row->key;

		$this->setModule($this->install ? new Module($this->id) : new ModuleFake($this->id));
		$module = $this->getModule();
		$module->getVersion();
	}

	public function install()
	{
		if( $this->install )
		{
			throw new \InvalidArgumentException("The module has already been installed");
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

	public function uninstall()
	{
		//
	}
}