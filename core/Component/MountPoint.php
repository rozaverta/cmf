<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 28.07.2018
 * Time: 14:08
 */

namespace EApp\Component;


use EApp\Component\Scheme\RouteSchemeDesigner;
use EApp\ModuleCore;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Traits\Get;
use EApp\Support\Traits\GetIdentifier;
use EApp\System\Interfaces\ModuleComponent;

class MountPoint implements ModuleComponent
{
	use GetIdentifier;
	use Get;

	private $scheme;

	private $module;

	private $module_id;

	protected $path;

	protected $type;

	protected $items;

	protected $length = -1;

	public function __construct( RouteSchemeDesigner $scheme )
	{
		$this->id = $scheme->id;
		$this->module_id = $scheme->module_id;
		$this->scheme = $scheme;
		$this->path = $scheme->path;
		$this->type = $scheme->type;
		$this->items = $scheme->properties;
	}

	/**
	 * @param int $id
	 * @param null $class_name
	 * @return MountPoint
	 * @throws NotFoundException
	 * @throws \InvalidArgumentException
	 */
	static public function load( int $id, $class_name = null )
	{
		$builder = \DB
			::table("module_router")
			->whereId($id);

		$result = $builder->getConnection()->selectOne(
			$builder->toSql(), $builder->getBindings(), true, RouteSchemeDesigner::class
		);

		if( !$result )
		{
			throw new NotFoundException("Mount point '{$id}' not found");
		}

		if( is_null($class_name) )
		{
			$class_name = self::class;
		}

		$mount_point = new $class_name($result);
		if( $mount_point instanceof self )
		{
			return $mount_point;
		}

		throw new \InvalidArgumentException("Invalid instance for MountPoint");
	}

	/**
	 * @return \EApp\Component\Module
	 */
	public function getModule(): Module
	{
		if( !isset($this->module) )
		{
			$this->module = $this->module_id === 0 ? new ModuleCore() : Module::cache($this->module_id);
		}
		return $this->module;
	}

	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}
}
