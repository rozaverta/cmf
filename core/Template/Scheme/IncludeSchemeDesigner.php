<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.09.2017
 * Time: 0:05
 */

namespace EApp\Template\Scheme;


use EApp\Component\Module;

class IncludeSchemeDesigner
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
	 * ModuleInstance name.
	 *
	 * @var string
	 */
	public $module_name;

	/**
	 * Relative file url.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Package title.
	 *
	 * @var int
	 */
	public $position;

	/**
	 * Full file url path.
	 *
	 * @var string
	 */
	public $full_path;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->module_id = (int) $this->module_id;
		$this->position = (int) $this->position;

		$path = [];
		if( $this->module_id > 0 )
		{
			$path["module"] = Module::cache($this->module_id)->get("path");
		}

		$this->full_path = \E\Path($this->path, false, $path);
	}
}