<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.09.2017
 * Time: 23:21
 */

namespace EApp\Schemes;

class PluginSchemeDesigner extends _ModuleSchemeDesigner
{
	/**
	 * Plugin unique identifier.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Plugin access name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Plugin title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Plugin visible.
	 *
	 * @var bool
	 */
	public $visible;

	/**
	 * Can be used as an abbreviated name for package templates.
	 *
	 * @var boolean
	 */
	public $short = false;

	/**
	 * Real class name.
	 *
	 * @var string
	 */
	public $class_name;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
		$this->visible = $this->visible > 0;
	}

	/**
	 * Package (module) name.
	 *
	 * @return  string
	 */
	public function getPackageName(): string
	{
		if( $this->module_id > 0 )
		{
			$module = $this->getModule();
			if( strpos($this->class_name, "\\") === false )
			{
				$this->class_name = $module->getNamespace() . "Plugin\\" . $this->class_name;
			}
			return $module->getName();
		}
		else
		{
			return $this->name;
		}
	}
}