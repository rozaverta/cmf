<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace EApp\Component;

use EApp\Cache;
use EApp\Interfaces\PhpExportSerializeInterface;
use EApp\ModuleCore;
use EApp\Exceptions\NotFoundException;
use EApp\Interfaces\Arrayable;
use EApp\Traits\CacheIdentifierInstanceTrait;
use EApp\Traits\CachePhpExportIdentifierTrait;
use EApp\Traits\GetTrait;
use EApp\Traits\GetIdentifierTrait;

/**
 * Class Module
 *
 * @package EApp\Component
 */
class Module implements Arrayable, PhpExportSerializeInterface
{
	use GetIdentifierTrait;
	use GetTrait;
	use CachePhpExportIdentifierTrait;
	use CacheIdentifierInstanceTrait {
		cache as defaultCache;
	}

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

	public function __construct( int $id )
	{
		$id = (int) $id;
		$this->fill($id, $this->fetch($id));
	}

	/**
	 * GetTrait module name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * GetTrait module key name
	 *
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * GetTrait module title
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
	 * GetTrait module version
	 *
	 * @return string
	 */
	public function getVersion(): string
	{
		return $this->version;
	}

	/**
	 * GetTrait module path
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * GetTrait module namespace
	 *
	 * @return string
	 */
	public function getNamespace(): string
	{
		return $this->name_space;
	}

	/**
	 * GetTrait all support addons
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
			throw new NotFoundException("ModuleComponentInterface '{$this->id}' not found");
		}

		$name_space = trim($row->name_space, '\\');
		$class = $name_space . '\\Module';
		if( !class_exists($class, true) )
		{
			throw new NotFoundException("ModuleComponentInterface '{$row->name}' not found");
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
			'name_space' => $module_config->getNamespace(),
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

	/**
	 * Load (or create) module instance from local cache
	 *
	 * @param int $id
	 * @return Module
	 */
	public static function cache( int $id )
	{
		if( ! self::cacheIs($id) && $id === 0 )
		{
			$module = new ModuleCore();
			self::setCache(0, $module->setLoadedFromCache());
		}

		return self::defaultCache($id);
	}

	protected static function createCache( int $id ): Cache
	{
		return new Cache($id, 'modules');
	}

	protected function importCacheData( $data )
	{
		$this->fill($data["id"], $data);
	}

	protected function exportCacheData()
	{
		return [
			'id' => $this->getId(),
			'name' => $this->name,
			'key' => $this->key,
			'route' => $this->route,
			'title' => $this->title,
			'version' => $this->version,
			'path' => $this->path,
			'name_space' => $this->name_space,
			'support' => $this->support,
			'extra' => $this->items
		];
	}
}