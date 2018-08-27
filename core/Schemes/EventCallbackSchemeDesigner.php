<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 3:51
 */

namespace EApp\Schemes;

class EventCallbackSchemeDesigner extends _ModuleSchemeDesigner
{
	/**
	 * @var int
	 */
	public $id;

	public $priority;

	public $class_name;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
	}

	public function getClassName()
	{
		$class_name = trim($this->class_name, "\\");
		return strpos($class_name, '\\') === false ? ($this->getModule()->getNamespace() . 'Events\\' . $class_name) : $class_name;
	}

	public function createEvent( ...$args )
	{
		$class_name = $this->getClassName();
		return new $class_name( ...$args );
	}
}