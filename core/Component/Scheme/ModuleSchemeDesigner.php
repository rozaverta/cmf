<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.09.2017
 * Time: 2:51
 */

namespace EApp\Component\Scheme;

use EApp\Database\Schema\SchemeDesigner;
use EApp\Support\Exceptions\NotFoundException;

class ModuleSchemeDesigner extends SchemeDesigner
{
	/**
	 * ModuleComponent unique identifier in the database table.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * ModuleComponent name (base directory for namespace)
	 *
	 * @var string
	 */
	public $name;

	public $install;

	public $title;

	public $route;

	public $support;

	public $version;

	public $data;

	public $key;

	public $name_space;

	public $path;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->install = $this->install > 0;
		$name_space = empty($this->name_space) ? ('MD\\' . $this->name) : trim($this->name_space, '\\');
		$class = $name_space . '\\Module';

		if( ! class_exists($class, true) )
		{
			throw new NotFoundException("Module '{$this->name}' not found");
		}

		/**
		 * @var \EApp\Component\ModuleConfig $module
		 */

		$module = new $class();
		if( $module->name !== $this->name )
		{
			throw new \InvalidArgumentException("Failure config data for module '{$this->name}'");
		}

		$this->title = $module->title;
		$this->route = $module->route;
		$this->support = $module->support;
		$this->version = $module->version;
		$this->data = $module->extra;
		$this->key = $module->getKey();
		$this->name_space = $module->getNameSpace();
		$this->path = $module->getPath();
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return
			[
				"id" => $this->id,
				"name" => $this->name,
				"title" => $this->title,
				"route" => $this->route,
				"support" => $this->support,
				"version" => $this->version,
				"data" => $this->data,
				"key" => $this->key,
				"name_space" => $this->name_space,
				"path" => $this->path,
			];
	}
}