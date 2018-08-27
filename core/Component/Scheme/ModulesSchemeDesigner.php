<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.09.2017
 * Time: 2:51
 */

namespace EApp\Component\Scheme;

use EApp\Database\Schema\SchemeDesigner;
use EApp\Exceptions\NotFoundException;
use EApp\Component\ModuleConfig;

class ModulesSchemeDesigner extends SchemeDesigner
{
	/**
	 * ModuleComponentInterface unique identifier in the database table.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * ModuleComponentInterface name (base directory for namespace)
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

	/**
	 * @var \EApp\Component\ModuleConfig $config
	 */
	protected $config;

	public function __construct()
	{
		$this->id = (int) $this->id;
		$this->install = $this->install > 0;
		$name_space = empty($this->name_space) ? $this->name : trim($this->name_space, '\\');
		$class_name = $name_space . '\\Module';

		if( ! class_exists($class_name, true) )
		{
			throw new NotFoundException("The '{$this->name}' module not found");
		}

		$this->config = new $class_name();
		if( $this->config->name !== $this->name )
		{
			throw new \InvalidArgumentException("Failure config data for module '{$this->name}'");
		}

		$this->title = $this->config->title;
		$this->route = $this->config->route;
		$this->support = $this->config->support;
		$this->version = $this->config->version;
		$this->data = $this->config->extra;
		$this->key = $this->config->getKey();
		$this->name_space = $this->config->getNamespace();
		$this->path = $this->config->getPath();
	}

	/**
	 * GetTrait the instance as an array.
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

	/**
	 * @return \EApp\Component\ModuleConfig
	 */
	public function getConfig(): ModuleConfig
	{
		return $this->config;
	}
}