<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 3:12
 */

namespace EApp\Proto;

use EApp\Support\Exceptions\PageNotFoundException;
use EApp\Component\Module;

abstract class Router
{
	/**
	 * @var \EApp\Component\Module
	 */
	protected $module;

	protected $prop  = [];
	protected $ctrl  = null;
	protected $type  = null;
	protected $match = null;
	protected $mask  = '';

	abstract public function ready();

	public function __construct( Module $module, $prop = [], $type = null, $mask = '', $match = null )
	{
		$name = get_class($this);
		if( strpos($name, $module->get('name_space')) !== 0 )
		{
			throw new \Exception("Invalid current module");
		}

		$this->module = $module;
		$this->prop = $prop;
		$this->type = $type;
		$this->match = $match;
		$this->mask = $mask;
	}

	public function controller()
	{
		if( !$this->ctrl )
		{
			throw new \Exception("Controller not used", 500);
		}
		else
		{
			return $this->ctrl;
		}
	}

	// protected

	protected function throw404()
	{
		throw new PageNotFoundException;
	}

	protected function checkController( $name )
	{
		return class_exists( $this->module->get('name_space') . "Controller\\" . $name, true );
	}

	protected function setController( $name, array $instanceControllerData = [] )
	{
		$class = $this->module->get('name_space') . "Controller\\" . $name;
		return $this->useController(new $class( $this->module, $instanceControllerData ));
	}

	protected function useController( Controller $controller )
	{
		$this->ctrl = $controller;
		return true;
	}
}