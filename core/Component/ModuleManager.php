<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace EApp\Component;

use EApp\Cache;
use EApp\Support\Collection;
use EApp\Support\Interfaces\Arrayable;
use EApp\Support\Str;
use EApp\Support\Traits\Get;
use EApp\Support\Traits\SingletonInstance;

/**
 * Class ModuleManager
 * @package System
 * @method static ModuleManager getInstance()
 */
class ModuleManager implements Arrayable
{
	use Get;
	use SingletonInstance;

	protected $items = [];
	protected $names = [];

	protected function __construct()
	{
		$cache = new Cache('name_id', 'modules');

		if( $cache->ready() )
		{
			$this->items = $cache->import();
			foreach($this->items as $item)
			{
				$this->names[$item["name"]] = $item["id"];
			}
		}
		else {
			foreach( (new QueryModules())
				         ->filter('install', true)
				         ->get() as $item ) {

				/** @var \EApp\Component\Scheme\ModuleSchemeDesigner $item */
				$this->names[$item->name] = $item->id;
				$this->items[$item->id] = [
					"id" => $item->id,
					"name" => $item->name,
					"title" => $item->title,
					"route" => $item->route,
					"path" => $item->path,
				];
			}
			$cache->export($this->items);
		}
	}

	/**
	 * Get all install modules collection.
	 *
	 * @return Collection
	 */
	public function getAll()
	{
		return new Collection($this->items);
	}

	/**
	 * Each module items.
	 *
	 * @param \Closure $callback
	 * @return $this
	 */
	public function each( \Closure $callback )
	{
		foreach( $this->items as $name => $properties )
		{
			if( $callback($properties, $name) === false )
			{
				break;
			}
		}

		return $this;
	}

	/**
	 * Has module or modules install.
	 *
	 * @param array|string|number $name
	 * @return bool
	 */
	public function installed( $name )
	{
		if( ! is_array($name) )
		{
			$name = [$name];
		}

		if( ! count($name) )
		{
			return false;
		}

		foreach( $name as $module_name )
		{
			if( is_numeric($module_name) )
			{
				if( !$this->offsetExists($module_name) )
				{
					return false;
				}
			}
			else if( $this->getId($module_name) === false )
			{
				return false;
			}
		}

		return true;
	}

	public function getModuleInstance( $name, $cached = true )
	{
		if( is_numeric($name) )
		{
			return new Module((int) $name, $cached);
		}

		$id = $this->getId($name);
		if( $id === false )
		{
			throw new \Exception("ModuleComponent '{$name}' not found");
		}

		return new Module($id, $cached);
	}

	public function getId( $name )
	{
		$name = Str::studly($name);
		return isset($this->names[$name]) ? $this->names[$name] : false;
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return $this->items;
	}
}