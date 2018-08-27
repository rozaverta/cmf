<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2018
 * Time: 11:47
 */

namespace EApp\Schemes;

class SchemeTablesSchemeDesigner extends _ModuleSchemeDesigner
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
	public $description;

	/**
	 * @var string
	 */
	public $version;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
	}
}