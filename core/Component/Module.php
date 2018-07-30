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

	protected $id = 0;

	protected $items = [];

	protected $support = [];

	protected $is_install = true;

	public function __construct( $id, $cached = true )
	{
		$this->id = (int) $id;

		if( $cached )
		{
			$cache = new Cache( $this->id, 'modules' );
			if( !$cache->ready() )
			{
				$row = $this->fetch();
				$cache->write($row);
			}
			else
			{
				$row = $cache->getContentData();
			}
		}
		else
		{
			$row = $this->fetch();
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

	public function getId()
	{
		return $this->id;
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

	protected function load( $id, ModuleConfig $module )
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

	protected function fetch()
	{
		$builder = \DB::table("modules")->whereId($this->id);
		if( $this->is_install )
		{
			$builder->where('install', true);
		}

		$row = $builder->first();
		if( !$row )
		{
			throw new NotFoundException("ModuleComponent '{$this->id}' not found");
		}

		$name_space = empty($row->name_space) ? ('MD\\' . $row->name) : trim($row->name_space, '\\');
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

		return $this->load( $row->id, $module );
	}
}