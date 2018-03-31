<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.09.2017
 * Time: 0:05
 */

namespace EApp\Template\Scheme;


class PackageSchemeDesigner
{
	/**
	 * Plugin unique identifier.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * ModuleInstance identifier.
	 *
	 * @var int
	 */
	public $module_id;

	/**
	 * Package access name.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Package title.
	 *
	 * @var string
	 */
	public $title;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
	}
}