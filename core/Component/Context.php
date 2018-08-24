<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.08.2018
 * Time: 16:27
 */

namespace EApp\Component;

use EApp\Component\Scheme\ContextSchemeDesigner;
use EApp\Exceptions\NotFoundException;
use EApp\Interfaces\Arrayable;
use EApp\Traits\GetIdentifierTrait;

class Context implements Arrayable
{
	use GetIdentifierTrait;

	/**
	 * @var ContextSchemeDesigner
	 */
	private $instance;

	/**
	 * @var int[]
	 */
	private $module_ids;

	public function __construct( ContextSchemeDesigner $instance, array $module_ids = [] )
	{
		$this->id = $instance->id;
		$this->instance = $instance;
		$this->module_ids = [];
	}

	public static function createFromName( string $name ): Context
	{
		return self::createFrom(trim($name), "name");
	}

	public static function createFromId( int $id ): Context
	{
		return self::createFrom($id, "id");
	}

	public static function createFromSchemeDesignerInstance( ContextSchemeDesigner $instance ): Context
	{
		$ids = \DB
			::table("context_module_link")
				->where("context_id", $instance->id)
				->get(['module_id'])
				->map(static function($row) {
					return (int) $row->module_id;
				})
				->getAll();

		return new Context($instance, $ids);
	}

	public static function createFromData( array $data ): Context
	{
		if( ! isset($data["id"]) || ! is_int($data["id"]) ) throw new \InvalidArgumentException("Invalid data properties");

		$ref = new \ReflectionClass(ContextSchemeDesigner::class);

		/** @var ContextSchemeDesigner $instance */
		$instance = $ref->newInstanceWithoutConstructor();
		$instance->id = $data["id"];
		$instance->name = $data["name"] ?? "";
		$instance->type = $data["type"] ?? "";
		$instance->title = $data["title"] ?? "";
		$instance->comment = $data["comment"] ?? "";
		$instance->host = $data["host"] ?? "";
		$instance->host_port = $data["host_port"] ?? 0;
		$instance->host_scheme = $data["host_scheme"] ?? "";
		$instance->path = $data["path"] ?? "";
		$instance->query = $data["query"] ?? [];
		$instance->is_default = $data["is_default"] ?? false;

		return isset($data["module_ids"]) && is_array($data["module_ids"])
			? new Context($instance, $data["module_ids"])
			: self::createFromSchemeDesignerInstance($instance);
	}

	private static function createFrom( $value, string $field ): Context
	{
		$builder = \DB
			::table("context")
				->where($field, $value)
				->limit(1);

		/** @var ContextSchemeDesigner|false $instance */
		$instance = $builder
			->getConnection()
			->selectOne( $builder->toSql(), $builder->getBindings(), true, ContextSchemeDesigner::class );

		if( !$instance )
			throw new NotFoundException("Context point '{$value}' not found");

		return self::createFromSchemeDesignerInstance($instance);
	}

	/**
	 * Raw result object
	 *
	 * @return ContextSchemeDesigner
	 */
	public function getSchemeDesignerInstance()
	{
		return $this->instance;
	}

	/**
	 * @return bool
	 */
	public function isHost(): bool
	{
		return $this->instance->isHost();
	}

	/**
	 * @return bool
	 */
	public function isQuery(): bool
	{
		return $this->instance->isQuery();
	}

	/**
	 * @return bool
	 */
	public function isPath(): bool
	{
		return $this->instance->isPath();
	}

	/**
	 * @return bool
	 */
	public function isSsl(): bool
	{
		return $this->instance->host_scheme === "https";
	}

	/**
	 * @return int[]
	 */
	public function getModuleIds(): array
	{
		return $this->module_ids;
	}

	/**
	 * @param Module $module
	 * @return bool
	 */
	public function hasModule( Module $module ): bool
	{
		return $this->hasModuleId( $module->getId() );
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function hasModuleId( int $id ): bool
	{
		return in_array($id, $this->module_ids, true);
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->instance->name;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->instance->type;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->instance->title;
	}

	/**
	 * @return string
	 */
	public function getComment(): string
	{
		return $this->instance->comment;
	}

	/**
	 * @return string
	 */
	public function getHost(): string
	{
		return $this->instance->host;
	}

	/**
	 * @return string
	 */
	public function getPort(): string
	{
		return $this->instance->host_port;
	}

	/**
	 * @return string
	 */
	public function getProtocol(): string
	{
		return $this->instance->host_scheme;
	}

	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->instance->path;
	}

	/**
	 * @return array
	 */
	public function getQuery(): array
	{
		return $this->instance->query;
	}

	/**
	 * @return bool
	 */
	public function isDefault(): bool
	{
		return $this->instance->is_default;
	}

	/**
	 * GetTrait the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$all = $this->instance->toArray();
		$all["module_ids"] = $this->getModuleIds();
		return $all;
	}
}