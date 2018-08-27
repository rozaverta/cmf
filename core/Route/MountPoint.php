<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 28.07.2018
 * Time: 14:08
 */

namespace EApp\Route;

use EApp\App;
use EApp\Component\Module;
use EApp\Schemes\ModuleRouterSchemeDesigner;
use EApp\Exceptions\NotFoundException;
use EApp\Traits\GetModuleComponentTrait;
use EApp\Traits\GetTrait;
use EApp\Traits\GetIdentifierTrait;
use EApp\Interfaces\ModuleComponentInterface;

class MountPoint implements ModuleComponentInterface
{
	use GetIdentifierTrait;
	use GetTrait;
	use GetModuleComponentTrait {
		getModuleId as getNativeModuleId;
	}

	private $scheme;

	private $module_id;

	protected $type;

	protected $rule;

	protected $items;

	protected $length = -1;

	public function __construct( ModuleRouterSchemeDesigner $scheme )
	{
		$this->id = $scheme->id;
		$this->module_id = $scheme->module_id;
		$this->scheme = $scheme;
		$this->type = $scheme->type;
		$this->rule = $scheme->rule;
		$this->items = $scheme->properties;

		if($this->rule === "comparator")
		{
			try
			{
				$this->rule = Parser::parse($this->rule, $this->items);
			}
			catch( \InvalidArgumentException $e )
			{
				App::Log($e);
				$this->rule = "path";
			}
		}
	}

	public static function __set_state( $data )
	{
		if( ! isset($data["id"], $data["module_id"], $data["type"]) || ! is_int($data["id"]) )
		{
			throw new \InvalidArgumentException(__CLASS__ . "::" . __METHOD__ . " 'id' property is not used");
		}

		$ref = new \ReflectionClass(ModuleRouterSchemeDesigner::class);

		/** @var ModuleRouterSchemeDesigner $scheme */
		$scheme = $ref->newInstanceWithoutConstructor();

		$scheme->id = $data["id"];
		$scheme->module_id = $data["module_id"];
		$scheme->properties = $data["items"] ?? [];
		$scheme->type = $data["type"];
		$scheme->rule = $data["rule"] ?? "";

		$instance = new self($scheme);
		if( isset($data["module"]) )
		{
			$instance->module = $data["module"];
		}

		return $instance;
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
			$builder->toSql(), $builder->getBindings(), true, ModuleRouterSchemeDesigner::class
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
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getModuleId(): int
	{
		return $this->hasModule() ? $this->getNativeModuleId() : $this->module_id;
	}

	/**
	 * @return bool
	 */
	public function isRule(): bool
	{
		return is_string($this->rule) ? strlen($this->rule) > 0 : ! is_null($this->rule);
	}

	/**
	 * @return mixed
	 */
	public function getRule()
	{
		return is_null($this->rule) ? "" : $this->rule;
	}

	protected function reloadModule()
	{
		$this->setModule(Module::cache($this->module_id));
	}
}