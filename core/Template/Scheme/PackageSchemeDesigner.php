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
	 * ModuleConfig identifier.
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

	/**
	 * Package version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Package readme.md data text.
	 *
	 * @var string
	 */
	public $readme = "";

	/**
	 * Package license.
	 *
	 * @var string
	 */
	public $license = "";

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
	}
}