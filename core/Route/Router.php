<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 3:12
 */

namespace EApp\Route;

use EApp\Controllers\Controller;
use EApp\Exceptions\PageNotFoundException;
use EApp\Traits\GetModuleComponentTrait;
use EApp\Interfaces\ModuleComponentInterface;

abstract class Router implements ModuleComponentInterface
{
	use GetModuleComponentTrait;

	protected $controller = null;

	protected $match = null;

	protected $mount_point;

	abstract public function ready();

	abstract static public function exists( string $controller, int $id ): bool;

	abstract static public function makeUrl( string $controller, int $id, string $context = null ): string;

	public function __construct( MountPoint $mount_point, $match = null )
	{
		$module = $mount_point->getModule();
		if( strpos(__CLASS__, $module->getNamespace()) !== 0 )
		{
			throw new \InvalidArgumentException("Invalid current module");
		}

		$this->match = $match;
		$this->setModule($module);
	}

	/**
	 * @return MountPoint
	 */
	public function getMountPoint()
	{
		return $this->mount_point;
	}

	public function getController()
	{
		if( !$this->controller )
		{
			throw new \RuntimeException("Controller not used", 500);
		}
		else
		{
			return $this->controller;
		}
	}

	// protected

	protected function throw404()
	{
		throw new PageNotFoundException;
	}

	protected function checkController( $name )
	{
		return class_exists( $this->getModule()->getNamespace() . "Controllers\\" . $name, true );
	}

	protected function setController( $name, array $instanceControllerData = [] )
	{
		$class = $this->getModule()->getNamespace() . "Controllers\\" . $name;
		return $this->useController(new $class( $this->getModule(), $instanceControllerData ));
	}

	protected function useController( Controller $controller )
	{
		$this->controller = $controller;
		return true;
	}
}