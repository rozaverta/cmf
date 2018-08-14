<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace EApp\Component;

use EApp\Cache;
use EApp\ModuleCore;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Interfaces\Arrayable;
use EApp\Support\Traits\Get;
use EApp\Support\Traits\GetIdentifier;

class Module implements Arrayable
{
	use GetIdentifier;
	use Get;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var bool
	 */
	protected $route;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var string
	 */
	protected $name_space;

	protected $items = [];

	protected $support = [];

	protected $is_install = true;

	private static $cache = [];

	public function __construct( int $id, bool $cached = true )
	{
		$id = (int) $id;

		if( $cached )
		{
			$cache = new Cache( $id, 'modules' );
			if( !$cache->ready() )
			{
				$row = $this->fetch( $id );
				$cache->write($row);
			}
			else
			{
				$row = $cache->getContentData();
			}
		}
		else
		{
			$row = $this->fetch($id);
		}

		$this->fill($id, $row);
	}

	public function __set_state( $data )
	{
		if( !isset($data["id"]) || ! is_int($data["id"]) )
		{
			throw new \InvalidArgumentException(__CLASS__ . "::" . __METHOD__ . " 'id' property is not used");
		}

		$id = $data["id"];
		if( isset(self::$cache[$id]) )
		{
			return self::$cache[$id];
		}

		/** @var self $module */

		if( $id > 0 )
		{
			$ref = new \ReflectionClass(self::class);
			$module = $ref->newInstanceWithoutConstructor();
			$module->fill($id, $data);
		}
		else
		{
			$module = new ModuleCore();
		}

		self::$cache[$id] = $module;
		return $module;
	}

	/**
	 * Load (or create) module instance from local cache
	 *
	 * @param int $id
	 * @return Module
	 */
	public static function cache( int $id ): Module
	{
		if( !isset(self::$cache[$id]) )
		{
			self::$cache[$id] = $id === 0 ? new ModuleCore() : new self($id, true);
		}

		return self::$cache[$id];
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get module key name
	 *
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * Get module title
	 *
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * Module use router
	 *
	 * @return bool
	 */
	public function isRoute(): bool
	{
		return $this->route;
	}

	/**
	 * Get module version
	 *
	 * @return string
	 */
	public function getVersion(): string
	{
		return $this->version;
	}

	/**
	 * Get module path
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Get module namespace
	 *
	 * @return string
	 */
	public function getNameSpace(): string
	{
		return $this->name_space;
	}

	/**
	 * Get all support addons
	 *
	 * @return array
	 */
	public function getSupport(): array
	{
		return $this->support;
	}

	/**
	 * Addons module is supported
	 *
	 * @param $name
	 * @return bool
	 */
	public function support( $name ): bool
	{
		return in_array($name, $this->support, true);
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$data = $this->items;
		$data['support'] = $this->support;
		return $data;
	}

	// -- protected

	protected function fetch( int $id )
	{
		$builder = \DB
			::table("modules")
			->whereId($id);

		if( $this->is_install )
		{
			$builder->where('install', true);
		}

		$row = $builder->first();
		if( !$row )
		{
			throw new NotFoundException("ModuleComponent '{$this->id}' not found");
		}

		$name_space = trim($row->name_space, '\\');
		$class = $name_space . '\\Module';
		if( !class_exists($class, true) )
		{
			throw new NotFoundException("ModuleComponent '{$row->name}' not found");
		}

		/**
		 * @var ModuleConfig $module
		 */
		$module = new $class();
		if( $module->name !== $row->name )
		{
			throw new \InvalidArgumentException("Failure config data for module '{$row->name}'");
		}

		if( $this->is_install && $module->version !== $row->version )
		{
			throw new \InvalidArgumentException("The current version of the '{$row->name}' module does not match the installed version of the module");
		}

		return $this->load( $row->id, $module, $row->version );
	}

	protected function load( $id, ModuleConfig $module_config, string $version )
	{
		$get = [
			'id' => $id,
			'name' => $module_config->name,
			'key' => $module_config->getKey(),
			'route' => $module_config->route,
			'title' => $module_config->title,
			'version' => $version,
			'path' => $module_config->getPath(),
			'name_space' => $module_config->getNameSpace(),
			'support' => $module_config->support,
			'extra' => []
		];

		foreach( $module_config->extra as $key => $value)
		{
			if( ! isset($get[$key]) )
			{
				$get["extra"][$key] = $value;
			}
		}

		return $get;
	}

	protected function fill( int $id, array $row )
	{
		$this->id = $id;
		$this->name = $row["name"];
		$this->key = $row["key"];
		$this->route = $row["route"];
		$this->title = $row["title"];
		$this->version = $row["version"];
		$this->path = $row["path"];
		$this->name_space = $row["name_space"];
		$this->support = $row["support"];
		$this->items = $row["extra"];
	}
}