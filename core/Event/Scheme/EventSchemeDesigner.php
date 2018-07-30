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

class EventSchemeDesigner
{
	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var int
	 */
	public $module_id;

	public $name;

	public $title;

	/**
	 * @var bool
	 */
	public $completable;

	/**
	 * @var null| \EApp\Component\Module
	 */
	protected $module = null;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
		$this->completable = $this->completable > 0;
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
}