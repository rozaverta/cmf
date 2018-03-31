<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace EApp\Component;

use EApp\Cache;
use EApp\Support\Exceptions\NotFoundException;
use EApp\Support\Interfaces\Arrayable;
use EApp\Support\Traits\Get;

class Module implements Arrayable
{
	use Get;

	protected $items = [];
	protected $support = [];

	public function __construct( $id, $cached = true )
	{
		$id = (int) $id;

		if( $cached )
		{
			$cache = new Cache( $id, 'modules' );
			if( !$cache->ready() )
			{
				$row = $this->fetch($id);
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

		$this->support = $row['support'];
		unset($row['support']);

		$this->items = $row;
	}

	/**
	 * @param $id
	 * @return Module
	 */
	public static function cache( $id )
	{
		static $cache = [];
		if( !isset($cache[$id]) )
		{
			$cache[$id] = new self($id, true);
		}

		return $cache[$id];
	}

	public function support( $name )
	{
		return in_array($name, $this->support, true);
	}

	public function toArray()
	{
		$data = $this->items;
		$data['support'] = $this->support;
		return $data;
	}

	protected function load( $id, ModuleInstance $module )
	{
		$get = [
			'id' => $id,
			'name' => $module->name,
			'key' => $module->getKey(),
			'route' => $module->route,
			'title' => $module->title,
			'version' => $module->version,
			'path' => $module->getPath(),
			'name_space' => $module->getNameSpace(),
			'support' => $module->support
		];

		foreach($module->data as $key => $value)
		{
			if( !isset($get[$key]) )
			{
				$get[$key] = $value;
			}
		}

		return $get;
	}

	private function fetch($id)
	{
		$row = \DB::table("modules")
			->whereId($id)
			->where('install', true)
			->first();

		if( !$row )
		{
			throw new NotFoundException("Module '{$id}' not found.");
		}

		$name_space = empty($row->name_space) ? ('MD\\' . $row->name) : trim($row->name_space, '\\');
		$class = $name_space . '\\Module';
		if( !class_exists($class, true) )
		{
			throw new NotFoundException("Module '{$row->name}' not found.");
		}

		/**
		 * @var ModuleInstance $module
		 */
		$module = new $class();
		if( $module->name !== $row->name )
		{
			throw new \InvalidArgumentException("Failure config data for module '{$row->name}'");
		}

		return $this->load( (int) $row->id, $module );
	}
}