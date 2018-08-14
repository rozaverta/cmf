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
use EApp\System\Events\ThrowableEvent;
use InvalidArgumentException;

class ModuleComponentCore extends ModuleComponentAbstract
{
	use Write;

	const UNINSTALL_DATABASE = 1;
	const UNINSTALL_ASSETS = 2;
	const UNINSTALL_APPLICATION = 4;

	private $status = false;

	public function __construct()
	{
		$this->name = '@core';
		$this->key = '@core';
		$this->name_space = 'EApp\\';
		$this->setModule( new ModuleCore() );

		$sys = new Prop("system");
		$this->install = $sys->get("install");
		if($this->install)
		{
			$this->version = $sys->get("version");
		}
	}

	public function install()
	{
		if($this->install)
		{
			throw new InvalidArgumentException("System already installed");
		}

		$manifest = $this->getManifest();
		if( $manifest->ready()->getType() !== "#/system" )
		{
			throw new InvalidArgumentException("Invalid manifest type");
		}

		$this->module_data = []; // clean module data
		$conf = new FileConfig("system");
		$this->status = $conf->getOr("status", $this->status);

		if( $conf->ready() && in_array($this->status, ["install-progress", "update-progress"]) )
		{
			throw new InvalidArgumentException("The installer is already running");
		}

		$conf
			->set("version", $manifest->get("version"))
			->set("name", $manifest->get("name"))
			->set("title", $manifest->get("title"))
			->set("description", $manifest->get("description"))
			->set("install", false)
			->set("status", "install-progress");

		if( ! $conf->write() )
		{
			throw new InvalidArgumentException("Can not write config file");
		}

		// add failure listener
		EventManager::listen("onThrowable", function(ThrowableEvent $event) use($conf) {
			if( $conf->get("status") === "install" ) return;

			$conf
				->set("status", $this->status ? $this->status : "install-error" )
				->set("install_error", $event->throwable->getMessage())
				->write() ||
			$this
				->addLogError("Can not update system configuration file after a failure. You must make changes manually!");
		});

		$event = new Events\ModuleInstallDriverEvent($this, [
			"id" => 0,
			"manifest" => $manifest,
			"config" => $conf
		]);

		$dispatcher = EventManager::dispatcher($event->getName());
		$this->addManifestListeners($dispatcher);

		$dispatcher
			->dispatch(
				$event, function ($result) {
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
			->set("status", $this->status ? $this->status : "install")
			->setNull("install_error")
			->write();

		if( !$write )
		{
			throw new InvalidArgumentException("Can not complete write config file");
		}

		$this->addLogDebug("System is successfully installed");
		$dispatcherClone->complete();
		return $this;
	}

	public function update(bool $force = false)
	{
		// TODO: Implement update() method.
	}

	public function uninstall( int $flag = 0 )
	{
		// TODO: Implement remove() method.
	}
}