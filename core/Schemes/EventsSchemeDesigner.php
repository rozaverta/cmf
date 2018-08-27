<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 3:51
 */

namespace EApp\Schemes;

class EventsSchemeDesigner extends _ModuleSchemeDesigner
{
	/**
	 * @var int
	 */
	public $id;

	public $name;

	public $title;

	/**
	 * @var bool
	 */
	public $completable;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
		$this->completable = $this->completable > 0;
	}
}