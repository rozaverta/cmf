<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 3:51
 */

namespace EApp\Event\Scheme;

use EApp\Component\Module;
use EApp\ModuleCore;

class EventCallbackSchemeDesigner
{
	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var int
	 */
	public $module_id;

	public $priority;

	public $class_name;

	protected $full_class_name = null;

	/**
	 * @var null| \EApp\Component\Module
	 */
	protected $module = null;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
		$this->full_class_name = $this->class_name;
	}

	/**
	 * @return \EApp\Component\Module
	 */
	public function getModule()
	{
		if( is_null($this->module) )
		{
			$this->module = $this->module_id > 0 ? Module::cache($this->module_id) : new ModuleCore();
		}
		return $this->module;
	}

	public function getClassName()
	{
		if( strpos($this->full_class_name, '\\') === false )
		{
			$this->full_class_name = $this->getModule()->get("name_space") . $this->full_class_name;
		}
		return $this->full_class_name;
	}

	public function createEvent( ...$args )
	{
		$class_name = $this->getClassName();
		return new $class_name( ...$args );
	}
}