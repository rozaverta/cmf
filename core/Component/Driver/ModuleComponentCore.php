<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.04.2018
 * Time: 2:49
 */

namespace EApp\Component\Driver;

use EApp\Component\Driver\Tools\FileConfig;
use EApp\Event\EventManager;
use EApp\ModuleCore;
use EApp\Prop;
use EApp\Support\Traits\Write;
use InvalidArgumentException;

class ModuleComponentCore extends ModuleComponentAbstract
{
	use Write;

	public function __construct()
	{
		$this->name = '@core';
		$this->key = '@core';
		$this->name_space = 'EApp\\';
		$this->module = new ModuleCore();

		$sys = new Prop("system");
		$this->install = $sys->get("install");
		if($this->install)
		{
			$this->version = $sys->get("version");
		}
	}

	/**
	 * @return \EApp\Component\Module | null
	 */
	public function getModule()
	{
		return $this->module;
	}

	public function install()
	{
		if($this->install)
		{
			throw new InvalidArgumentException("System already installed");
		}

		$this->module_data = []; // clean module data
		$manifest = $this->getManifest();
		$conf = new FileConfig("config");
		if( $conf->ready() && $conf->get("progress") )
		{
			throw new InvalidArgumentException("The installer is already running");
		}

		$conf
			->set("version", $manifest->get("version"))
			->set("name", $manifest->get("name"))
			->set("title", $manifest->get("title"))
			->set("description", $manifest->get("description"))
			->set("install", false)
			->set("progress", true);

		if( !$conf->write() )
		{
			throw new InvalidArgumentException("Can not write config file");
		}

		$dispatcher = EventManager::dispatcher("onComponentDriverAction");
		$this->addManifestListeners($dispatcher);

		$dispatcher
			->dispatch(
				new Events\ModuleInstallEvent($this, [
					"id" => 0,
					"manifest" => $manifest,
					"config" => $conf
				]), function ($result) {
					if( is_array($result) ) {
						$this->module_data = array_merge($this->module_data, $result);
					}
				}
			);

		$dispatcherClone = $dispatcher->getCompletableClone();

		$this->setModuleData();

		$this->installData();

		$write = $conf
			->set("install", true)
			->set("progress", null)
			->write();

		if( !$write )
		{
			throw new InvalidArgumentException("Can not complete write config file");
		}

		$dispatcherClone->complete();
		return $this;
	}

	public function update()
	{
		// TODO: Implement update() method.
	}

	public function remove()
	{
		// TODO: Implement remove() method.
	}
}
