<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2018
 * Time: 16:43
 */

namespace EApp\Schemes;

use EApp\Support\Json;

class ControllersSchemeDesigner extends _ModuleSchemeDesigner
{
	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var string
	 */
	public $class_name;

	/**
	 * @var bool
	 */
	public $dynamic;

	/**
	 * @var array
	 */
	public $properties;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
		$this->dynamic = $this->dynamic > 0;
		$this->properties = Json::getArrayProperties($this->properties);
	}
}