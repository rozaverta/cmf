<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.09.2017
 * Time: 23:21
 */

namespace EApp\Plugin\Scheme;

use EApp\Component\Module;
use EApp\Database\Schema\SchemeDesigner;

class PluginSchemeDesigner extends SchemeDesigner
{
	/**
	 * Plugin unique identifier.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * ModuleConfig identifier.
	 *
	 * @var int
	 */
	public $module_id;

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

	/**
	 * Package (module) name.
	 *
	 * @var string
	 */
	public $package_name;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
		$this->visible = $this->visible > 0;

		if( $this->module_id > 0 )
		{
			$module = Module::cache($this->module_id);
			if( strpos($this->class_name, "\\") === false )
			{
				$this->class_name = $module->getNameSpace() . "Plugin\\" . $this->class_name;
			}
			$this->package_name = $module->getName();
		}
		else
		{
			$this->package_name = $this->name;
		}
	}
}